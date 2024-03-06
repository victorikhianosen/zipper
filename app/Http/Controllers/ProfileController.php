<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Notifications\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use ImageKit\ImageKit;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Throwable;

class ProfileController extends Controller
{
    public function updateUserProfile(UpdateProfileRequest $request): JsonResponse
    {
        $this->authUser()->update($request->validated());

        return $this->success('Profile updated successfully.', new UserResource($this->authUser()));
    }

    public function updateUserProfilePhoto(UpdateAvatarRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('avatar');
        $imageKit = new ImageKit('publicKey', 'privateKey', config('app.url'));
        try {
            $uploadFile = $imageKit->uploadFile([
                'file' => $file,
                'fileName' => uniqid().'.'.$file->getClientOriginalExtension(),
            ]);
        } catch (Throwable $e) {
            throw new BadRequestException($e->getMessage(), 400);
        }
        if ($uploadFile->error) {
            throw new BadRequestException($uploadFile->error->message, 400); // @phpstan-ignore-line
        }
        $this->authUser()->update(['avatar' => $uploadFile->result->url]); // @phpstan-ignore-line

        return $this->success('Profile photo updated successfully.', new UserResource($this->authUser()));
    }

    public function updateUserPassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $this->authUser();
        if (! Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages(['old_password' => 'The old password is incorrect.']);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        $user->notify(new AppNotification(
            'Password updated',
            "Hi $user->first_name,\n ".
            'Your password was updated successfully at '.now()->format('h:ia'),
            ['database', 'firebase']
        ));

        return $this->success('Password updated successfully.');
    }
}
