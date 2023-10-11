import { startAuthentication } from "@simplewebauthn/browser";

(function () {
    fetch('/passkeys/login/options', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then((resp) => resp.json())
    .then((loginOptionsData) => {
        startAuthentication(loginOptionsData)
            .then((authenticationResponseJson) => {
                fetch('/passkeys/login/verify', {
                    method: 'POST',
                    body: JSON.stringify(authenticationResponseJson),
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                })
                .then((resp) => resp.json())
                .then((verifyData) => {
                    if (verifyData.verified) {
                        return window.location.reload();
                    }

                    new FilamentNotification()
                        .title('Error verifying passkey')
                        .danger()
                        .send();
                })
                .catch(() => {
                    new FilamentNotification()
                        .title('Error sending verification request')
                        .danger()
                        .send();
                });
            })
            .catch(() => {
                new FilamentNotification()
                    .title('Error authenticating')
                    .danger()
                    .send();
            });
    });
})();