import { startRegistration } from "@simplewebauthn/browser";

window.startPasskeyRegistration = () => {
    fetch('/passkeys/register/options', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
        .then((resp) => resp.json())
        .then((registerationOptionsData) => {
            startRegistration(registerationOptionsData)
                .then((registerationResponseJson) => {
                    fetch('/passkeys/register/verify', {
                        method: 'POST',
                        body: JSON.stringify(registerationResponseJson),
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
                .catch((err) => {
                    console.log(err);
                    new FilamentNotification()
                        .title('Error registering passkey')
                        .danger()
                        .send();
                });
        });
};