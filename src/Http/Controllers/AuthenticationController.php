<?php

namespace Statview\Passkeys\Http\Controllers;

use App\Models\User;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Statview\Passkeys\Auth\CredentialSourceRepository;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;

class AuthenticationController extends Controller
{
    const CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY = 'publicKeyCredentialRequestOptions';

    public function generateOptions(Request $request)
    {
        $pkRequestOptions = PublicKeyCredentialRequestOptions::create(random_bytes(length: 32));

        $pkRequestOptions->userVerification = 'required';
        $pkRequestOptions->rpId = parse_url(config('app.url'), PHP_URL_HOST);

        $serializedOptions = $pkRequestOptions->jsonSerialize();

        $request->session()->put(
            self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY,
            $serializedOptions,
        );

        return $serializedOptions;
    }

    public function verify(Request $request, ServerRequestInterface $serverRequest)
    {
        $pkSourceRepo = new CredentialSourceRepository();

        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        $algorithmManager = Manager::create()
            ->add(
                ES256::create(),
                ES256K::create(),
                ES384::create(),
                ES512::create(),
                RS256::create(),
                RS384::create(),
                RS512::create(),
                PS256::create(),
                PS384::create(),
                PS512::create(),
                Ed256::create(),
                Ed512::create(),
            );

        $responseValidator = AuthenticatorAssertionResponseValidator::create(
            publicKeyCredentialSourceRepository: $pkSourceRepo,
            tokenBindingHandler: null,
            extensionOutputCheckerHandler: ExtensionOutputCheckerHandler::create(),
            algorithmManager: $algorithmManager,
        );

        $pkCredentialLoader = PublicKeyCredentialLoader::create(
            AttestationObjectLoader::create($attestationManager),
        );

        $publicKeyCredential = $pkCredentialLoader->load(json_encode($request->all()));

        $authenticatorAssertionResponse = $publicKeyCredential->response;

        if (! $authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            throw ValidationException::withMessages([
                'id' => 'Invalid response type',
            ]);
        }

        $publicKeyCredentialSource = $responseValidator->check(
            credentialId: $publicKeyCredential->rawId,
            authenticatorAssertionResponse: $authenticatorAssertionResponse,
            publicKeyCredentialRequestOptions: PublicKeyCredentialRequestOptions::createFromArray(
                $request->session()->get(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY)
            ),
            request: $serverRequest,
            userHandle: $authenticatorAssertionResponse->userHandle,
        );

        $request->session()->forget(self::CREDENTIAL_REQUEST_OPTIONS_SESSION_KEY);

        $user = User::query()
            ->findOrFail($publicKeyCredentialSource->userHandle);

        Auth::login($user);

        return [
            'verified' => true,
        ];
    }
}