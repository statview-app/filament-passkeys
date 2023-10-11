<?php

namespace Statview\Passkeys\Auth;

use App\Models\User;
use Statview\Passkeys\Models\Passkey;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $passkey = Passkey::query()
            ->where('credential_id', base64_encode($publicKeyCredentialId))
            ->first();

        if (! $passkey) {
            return null;
        }

        return PublicKeyCredentialSource::createFromArray($passkey->credential_data);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return User::query()
            ->with('passkeys')
            ->where('users.id', $publicKeyCredentialUserEntity->id)
            ->first()
            ->passkeys
            ->toArray();
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $user = User::query()
            ->findOrFail($publicKeyCredentialSource->userHandle);

        $passkey = $user->passkeys()
            ->firstWhere('credential_id', base64_encode($publicKeyCredentialSource->publicKeyCredentialId));

        if (! $passkey) {
            $passkey = $user->passkeys()->create([
                'credential_id' => $publicKeyCredentialSource->publicKeyCredentialId,
            ]);
        }

        $passkey->update([
            'credential_data' => $publicKeyCredentialSource->jsonSerialize(),
        ]);
    }
}