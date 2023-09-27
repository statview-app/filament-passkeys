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

        return PublicKeyCredentialSource::createFromArray($passkey->public_key);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return User::query()
            ->with('passkeys')
            ->where('id', $publicKeyCredentialUserEntity->getId())
            ->first()
            ->passkeys
            ->toArray();
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $user = User::query()
            ->where('email', $publicKeyCredentialSource->getUserHandle())
            ->firstOrFail();

        $user->passkeys()->save(
            new Passkey([
                'credential_id' => $publicKeyCredentialSource->getPublicKeyCredentialId(),
                'public_key' => $publicKeyCredentialSource->jsonSerialize(),
            ])
        );
    }
}