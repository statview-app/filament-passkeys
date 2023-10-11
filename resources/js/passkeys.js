import {startAuthentication} from "@simplewebauthn/browser"
async function loginWithPasskey(email) {
    let resp = await fetch('/passkeys/login/options', {
        method: 'POST',
        body: JSON.stringify({
            email: email,
        }),
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    let attResp = await startAuthentication(await resp.json());

    let verificationResp = await fetch('/passkeys/login/verify', {
        method: 'POST',
        body: JSON.stringify(attResp),
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    let data = await verificationResp.json();

    if (data.verified) {
        return window.location.reload();
    }

    console.log('Something went wrong verifying the authentication.');
}

async function registerPasskey() {
    let resp = await fetch('/passkeys/register/options', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    let attResp = await startRegistration(await resp.json());

    let verificationResp = await fetch('/passkeys/register/verify', {
        method: 'POST',
        body: JSON.stringify(attResp),
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    });

    let data = await verificationResp.json();

    if (data.verified) {
        return window.location.reload();
    }

    console.log('Something went wrong verifying the registration.');
}

window.registerPasskey = registerPasskey;
window.loginWithPasskey = loginWithPasskey;
