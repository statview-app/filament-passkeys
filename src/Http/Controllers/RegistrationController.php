<?php

namespace Statview\Passkeys\Http\Controllers;

use App\Models\User;
use Cose\Algorithms;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Statview\Passkeys\Auth\CredentialSourceRepository;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;

class RegistrationController extends Controller
{
    const CREDENTIAL_CREATION_OPTIONS_SESSION_KEY = 'publicKeyCredentialCreationOptions';

    public function generateOptions(Request $request)
    {
        $rpEntity = PublicKeyCredentialRpEntity::create(
            name: config('app.name'),
            id: parse_url(config('app.url'), PHP_URL_HOST),
        );

        $user = auth()->user();

        $userEntity = PublicKeyCredentialUserEntity::create(
            name: $user->email,
            id: $user->id,
            displayName: $user->name,
        );

        $challenge = random_bytes(16);

        $supportedPublicKeyParams = collect([
            Algorithms::COSE_ALGORITHM_ES256,
            Algorithms::COSE_ALGORITHM_ES256K,
            Algorithms::COSE_ALGORITHM_ES384,
            Algorithms::COSE_ALGORITHM_ES512,
            Algorithms::COSE_ALGORITHM_RS256,
            Algorithms::COSE_ALGORITHM_RS384,
            Algorithms::COSE_ALGORITHM_RS512,
            Algorithms::COSE_ALGORITHM_PS256,
            Algorithms::COSE_ALGORITHM_PS384,
            Algorithms::COSE_ALGORITHM_PS512,
            Algorithms::COSE_ALGORITHM_ED256,
            Algorithms::COSE_ALGORITHM_ED512,
        ])
        ->map(
            fn ($algorithm) => PublicKeyCredentialParameters::create('public-key', $algorithm)
        )
        ->toArray();

        $pkCreationOptions = PublicKeyCredentialCreationOptions::create(
            rp: $rpEntity,
            user: $userEntity,
            challenge: $challenge,
            pubKeyCredParams: $supportedPublicKeyParams,
        )
        ->setAttestation(
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
        )
        ->setAuthenticatorSelection(
            AuthenticatorSelectionCriteria::create(),
        )
        ->setExtensions(AuthenticationExtensionsClientInputs::createFromArray([
            'credProps' => true,
        ]));

        $serializedOptions = $pkCreationOptions->jsonSerialize();

        if (! isset($serializedOptions['excludeCredentials'])) {
            $serializedOptions['excludeCredentials'] = [];
        }

        $serializedOptions['extensions'] = $serializedOptions['extensions']->jsonSerialize();
        $serializedOptions['authenticatorSelection'] = $serializedOptions['authenticatorSelection']->jsonSerialize();
        $serializedOptions['rp'] = $serializedOptions['rp']->jsonSerialize();
        $serializedOptions['user'] = $serializedOptions['user']->jsonSerialize();

        $request->session()->put(
            self::CREDENTIAL_CREATION_OPTIONS_SESSION_KEY,
            $serializedOptions,
        );

        return $serializedOptions;
    }

    public function verify(Request $request, ServerRequestInterface $serverRequest)
    {
        $pkSourceRepo = new CredentialSourceRepository();

        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        $responseValidator = AuthenticatorAttestationResponseValidator::create(
            attestationStatementSupportManager: $attestationManager,
            publicKeyCredentialSourceRepository: $pkSourceRepo,
            tokenBindingHandler: IgnoreTokenBindingHandler::create(),
            extensionOutputCheckerHandler: ExtensionOutputCheckerHandler::create(),
        );

        $pkCredentialLoader = PublicKeyCredentialLoader::create(
            AttestationObjectLoader::create($attestationManager)
        );

        $publicKeyCredential = $pkCredentialLoader->load(json_encode($request->all()));

        $authenticatorAttestationResponse = $publicKeyCredential->getResponse();

        if (! $authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            throw ValidationException::withMessages([
                'email' => 'Invalid response type'
            ]);
        }

        $publicKeyCredentialSource = $responseValidator->check(
            authenticatorAttestationResponse: $authenticatorAttestationResponse,
            publicKeyCredentialCreationOptions: PublicKeyCredentialCreationOptions::createFromArray(
                $request->session()->get(self::CREDENTIAL_CREATION_OPTIONS_SESSION_KEY)
            ),
            request: $serverRequest,
        );

        $user = auth()->user();

        $pkSourceRepo->saveCredentialSource($publicKeyCredentialSource);

        Auth::login($user);

        return [
            'verified' => true,
        ];
    }
}