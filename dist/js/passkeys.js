// resources/js/passkeys.js
async function startAuthentication(requestOptionsJSON, useBrowserAutofill = false) {
  if (!browserSupportsWebAuthn()) {
    throw new Error("WebAuthn is not supported in this browser");
  }
  let allowCredentials;
  if (requestOptionsJSON.allowCredentials?.length !== 0) {
    allowCredentials = requestOptionsJSON.allowCredentials?.map(toPublicKeyCredentialDescriptor);
  }
  const publicKey = {
    ...requestOptionsJSON,
    challenge: base64URLStringToBuffer(requestOptionsJSON.challenge),
    allowCredentials
  };
  const options = {};
  if (useBrowserAutofill) {
    if (!await browserSupportsWebAuthnAutofill()) {
      throw Error("Browser does not support WebAuthn autofill");
    }
    const eligibleInputs = document.querySelectorAll("input[autocomplete*=\'webauthn\']");
    if (eligibleInputs.length < 1) {
      throw Error('No <input> with `"webauthn"` in its `autocomplete` attribute was detected');
    }
    options.mediation = "conditional";
    publicKey.allowCredentials = [];
  }
  options.publicKey = publicKey;
  options.signal = webauthnAbortService.createNewAbortSignal();
  let credential;
  try {
    credential = await navigator.credentials.get(options);
  } catch (err) {
    throw identifyAuthenticationError({ error: err, options });
  }
  if (!credential) {
    throw new Error("Authentication was not completed");
  }
  const { id, rawId, response, type } = credential;
  let userHandle = undefined;
  if (response.userHandle) {
    userHandle = bufferToUTF8String(response.userHandle);
  }
  return {
    id,
    rawId: bufferToBase64URLString(rawId),
    response: {
      authenticatorData: bufferToBase64URLString(response.authenticatorData),
      clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
      signature: bufferToBase64URLString(response.signature),
      userHandle
    },
    type,
    clientExtensionResults: credential.getClientExtensionResults(),
    authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
  };
}
async function startRegistration(creationOptionsJSON) {
  if (!browserSupportsWebAuthn()) {
    throw new Error("WebAuthn is not supported in this browser");
  }
  const publicKey = {
    ...creationOptionsJSON,
    challenge: base64URLStringToBuffer(creationOptionsJSON.challenge),
    user: {
      ...creationOptionsJSON.user,
      id: utf8StringToBuffer(creationOptionsJSON.user.id)
    },
    excludeCredentials: creationOptionsJSON.excludeCredentials?.map(toPublicKeyCredentialDescriptor)
  };
  const options = { publicKey };
  options.signal = webauthnAbortService.createNewAbortSignal();
  let credential;
  try {
    credential = await navigator.credentials.create(options);
  } catch (err) {
    throw identifyRegistrationError({ error: err, options });
  }
  if (!credential) {
    throw new Error("Registration was not completed");
  }
  const { id, rawId, response, type } = credential;
  let transports = undefined;
  if (typeof response.getTransports === "function") {
    transports = response.getTransports();
  }
  let responsePublicKeyAlgorithm = undefined;
  if (typeof response.getPublicKeyAlgorithm === "function") {
    try {
      responsePublicKeyAlgorithm = response.getPublicKeyAlgorithm();
    } catch (e) {
    }
  }
  let responsePublicKey = undefined;
  if (typeof response.getPublicKey === "function") {
    let _publicKey = null;
    try {
      _publicKey = response.getPublicKey();
    } catch (e) {
    }
    if (_publicKey !== null) {
      responsePublicKey = bufferToBase64URLString(_publicKey);
    }
  }
  let responseAuthenticatorData;
  try {
    if (typeof response.getAuthenticatorData === "function") {
      responseAuthenticatorData = bufferToBase64URLString(response.getAuthenticatorData());
    }
  } catch (e) {
  }
  return {
    id,
    rawId: bufferToBase64URLString(rawId),
    response: {
      attestationObject: bufferToBase64URLString(response.attestationObject),
      clientDataJSON: bufferToBase64URLString(response.clientDataJSON),
      transports,
      publicKeyAlgorithm: responsePublicKeyAlgorithm,
      publicKey: responsePublicKey,
      authenticatorData: responseAuthenticatorData
    },
    type,
    clientExtensionResults: credential.getClientExtensionResults(),
    authenticatorAttachment: toAuthenticatorAttachment(credential.authenticatorAttachment)
  };
}
async function loginWithPasskey(email) {
  let resp = await fetch("/passkeys/login/options", {
    method: "POST",
    body: JSON.stringify({
      email
    }),
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    }
  });
  let attResp = await startAuthentication(await resp.json());
  let verificationResp = await fetch("/passkeys/login/verify", {
    method: "POST",
    body: JSON.stringify(attResp),
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    }
  });
  let data = await verificationResp.json();
  if (data.verified) {
    return window.location.reload();
  }
  console.log("Something went wrong verifying the authentication.");
}
async function registerPasskey() {
  let resp = await fetch("/passkeys/register/options", {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    }
  });
  let attResp = await startRegistration(await resp.json());
  let verificationResp = await fetch("/passkeys/register/verify", {
    method: "POST",
    body: JSON.stringify(attResp),
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json"
    }
  });
  let data = await verificationResp.json();
  if (data.verified) {
    return window.location.reload();
  }
  console.log("Something went wrong verifying the registration.");
}
var utf8StringToBuffer = function(value) {
  return new TextEncoder().encode(value);
};
var bufferToUTF8String = function(value) {
  return new TextDecoder("utf-8").decode(value);
};
var bufferToBase64URLString = function(buffer) {
  const bytes = new Uint8Array(buffer);
  let str = "";
  for (const charCode of bytes) {
    str += String.fromCharCode(charCode);
  }
  const base64String = btoa(str);
  return base64String.replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
};
var base64URLStringToBuffer = function(base64URLString) {
  const base64 = base64URLString.replace(/-/g, "+").replace(/_/g, "/");
  const padLength = (4 - base64.length % 4) % 4;
  const padded = base64.padEnd(base64.length + padLength, "=");
  const binary = atob(padded);
  const buffer = new ArrayBuffer(binary.length);
  const bytes = new Uint8Array(buffer);
  for (let i = 0;i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return buffer;
};
var browserSupportsWebAuthn = function() {
  return window?.PublicKeyCredential !== undefined && typeof window.PublicKeyCredential === "function";
};
var toPublicKeyCredentialDescriptor = function(descriptor) {
  const { id } = descriptor;
  return {
    ...descriptor,
    id: base64URLStringToBuffer(id),
    transports: descriptor.transports
  };
};
var isValidDomain = function(hostname) {
  return hostname === "localhost" || /^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i.test(hostname);
};
var identifyRegistrationError = function({ error, options }) {
  const { publicKey } = options;
  if (!publicKey) {
    throw Error("options was missing required publicKey property");
  }
  if (error.name === "AbortError") {
    if (options.signal instanceof AbortSignal) {
      return new WebAuthnError({
        message: "Registration ceremony was sent an abort signal",
        code: "ERROR_CEREMONY_ABORTED",
        cause: error
      });
    }
  } else if (error.name === "ConstraintError") {
    if (publicKey.authenticatorSelection?.requireResidentKey === true) {
      return new WebAuthnError({
        message: "Discoverable credentials were required but no available authenticator supported it",
        code: "ERROR_AUTHENTICATOR_MISSING_DISCOVERABLE_CREDENTIAL_SUPPORT",
        cause: error
      });
    } else if (publicKey.authenticatorSelection?.userVerification === "required") {
      return new WebAuthnError({
        message: "User verification was required but no available authenticator supported it",
        code: "ERROR_AUTHENTICATOR_MISSING_USER_VERIFICATION_SUPPORT",
        cause: error
      });
    }
  } else if (error.name === "InvalidStateError") {
    return new WebAuthnError({
      message: "The authenticator was previously registered",
      code: "ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED",
      cause: error
    });
  } else if (error.name === "NotAllowedError") {
    return new WebAuthnError({
      message: error.message,
      code: "ERROR_PASSTHROUGH_SEE_CAUSE_PROPERTY",
      cause: error
    });
  } else if (error.name === "NotSupportedError") {
    const validPubKeyCredParams = publicKey.pubKeyCredParams.filter((param) => param.type === "public-key");
    if (validPubKeyCredParams.length === 0) {
      return new WebAuthnError({
        message: 'No entry in pubKeyCredParams was of type "public-key"',
        code: "ERROR_MALFORMED_PUBKEYCREDPARAMS",
        cause: error
      });
    }
    return new WebAuthnError({
      message: "No available authenticator supported any of the specified pubKeyCredParams algorithms",
      code: "ERROR_AUTHENTICATOR_NO_SUPPORTED_PUBKEYCREDPARAMS_ALG",
      cause: error
    });
  } else if (error.name === "SecurityError") {
    const effectiveDomain = window.location.hostname;
    if (!isValidDomain(effectiveDomain)) {
      return new WebAuthnError({
        message: `${window.location.hostname} is an invalid domain`,
        code: "ERROR_INVALID_DOMAIN",
        cause: error
      });
    } else if (publicKey.rp.id !== effectiveDomain) {
      return new WebAuthnError({
        message: `The RP ID "${publicKey.rp.id}" is invalid for this domain`,
        code: "ERROR_INVALID_RP_ID",
        cause: error
      });
    }
  } else if (error.name === "TypeError") {
    if (publicKey.user.id.byteLength < 1 || publicKey.user.id.byteLength > 64) {
      return new WebAuthnError({
        message: "User ID was not between 1 and 64 characters",
        code: "ERROR_INVALID_USER_ID_LENGTH",
        cause: error
      });
    }
  } else if (error.name === "UnknownError") {
    return new WebAuthnError({
      message: "The authenticator was unable to process the specified options, or could not create a new credential",
      code: "ERROR_AUTHENTICATOR_GENERAL_ERROR",
      cause: error
    });
  }
  return error;
};
var toAuthenticatorAttachment = function(attachment) {
  if (!attachment) {
    return;
  }
  if (attachments.indexOf(attachment) < 0) {
    return;
  }
  return attachment;
};

class WebAuthnError extends Error {
  constructor({ message, code, cause, name }) {
    super(message, { cause });
    this.name = name ?? cause.name;
    this.code = code;
  }
}

class WebAuthnAbortService {
  createNewAbortSignal() {
    if (this.controller) {
      const abortError = new Error("Cancelling existing WebAuthn API call for new one");
      abortError.name = "AbortError";
      this.controller.abort(abortError);
    }
    const newController = new AbortController;
    this.controller = newController;
    return newController.signal;
  }
}
var webauthnAbortService = new WebAuthnAbortService;
var attachments = ["cross-platform", "platform"];
window.registerPasskey = registerPasskey;
window.loginWithPasskey = loginWithPasskey;
