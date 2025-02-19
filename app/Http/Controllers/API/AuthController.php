<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\SendOTP;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => 'required|integer|digits:9|unique:mahasiswa,nim',
            'nama' => 'required|regex:/^[a-zA-Z\s\.]*$/|max:50',
            'email' => 'required|email|unique:mahasiswa,email|ends_with:polban.ac.id|max:50',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error validasi.',
                'errors' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();

        $nomor_prodi = substr($validated['nim'], 2, 4);
        $program_studi = ProgramStudi::find($nomor_prodi);
        if (!$program_studi) {
            return response()->json([
                'message' => 'Nomor program studi tidak terdaftar untuk NIM tersebut.',
            ], 404);
        }

        $data = Arr::except($validated, 'password_confirmation');
        $data['password'] = Hash::make($data['password']);
        $data['status_aktif'] = true;
        $data['program_studi_nomor'] = $nomor_prodi;

        try {
            DB::transaction(function () use ($data) {
                $mahasiswa = Mahasiswa::create($data);
                event(new Registered($mahasiswa));
            });

            return response()->json(['message' => 'Registrasi berhasil.'], 201);
        } catch (\Throwable $t) {
            Log::error($t->getMessage());

            return response()->json([
                'message' => 'Registrasi gagal.',
                'error' => $t->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error validasi.',
                'errors' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();

        $mahasiswa = Mahasiswa::find($validated['nim']);

        if (!$mahasiswa || !Hash::check($validated['password'], $mahasiswa->password)) {
            return response()->json(['message' => 'NIM / kata sandi salah.'], 401);
        }

        if (!$mahasiswa->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email belum terverifikasi.'], 401);
        }

        $token = $mahasiswa->createToken('secret' . $mahasiswa->nim)->plainTextToken;

        return response()->json([
            'message' => 'Berhasil login.',
            'mahasiswa' => $mahasiswa,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => 'Logout berhasil.']);
        } catch (\Throwable $t) {
            Log::error($t->getMessage());

            return response()->json([
                'message' => 'Logout gagal.',
                'error' => $t->getMessage()
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error validasi.',
                'errors' => $validator->errors()
            ], 400);
        }

        $mahasiswa = Mahasiswa::find($request->email);
        if (!$mahasiswa) {
            return response()->json(['message' => 'Akun tidak terdaftar.'], 404);
        }

        try {
            $otp = rand(1000, 9999);
            DB::transaction(function () use ($request, $otp) {
                DB::table('password_resets')->insert([
                    'email' => $request->email,
                    'otp' => $otp,
                    'created_at' => Carbon::now()
                ]);
                Mail::to($request->email)->send(new SendOTP($otp));
            });

            return response()->json(['message' => 'Kode OTP berhasil dikirim.']);
        } catch (\Throwable $t) {
            Log::error($t->getMessage());

            return response()->json([
                'message' => 'Kode OTP gagal dikirim.',
                'error' => $t->getMessage()
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|numeric|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error validasi.',
                'errors' => $validator->errors()
            ], 400);
        }

        $result = DB::select("SELECT * FROM password_resets WHERE otp = ?", [$request->otp]);

        if (!$result) {
            return response()->json(['message' => 'Kode OTP tidak valid.'], 404);
        }

        return response()->json([
            'message' => 'Kode OTP valid.',
            'email' => $result['email']
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return response()->json(['message' => __($status)]);
    }
}
