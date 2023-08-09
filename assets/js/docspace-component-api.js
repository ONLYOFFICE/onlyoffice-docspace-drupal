(function () {
    if (!window.DocSpaceComponent) window.DocSpaceComponent = {};

    window.DocSpaceComponent = drupalSettings.DocSpaceComponent;

    var scriptTag = null;
    window.DocSpaceComponent.initScript = function (docSpaceUrl = DocSpaceComponent.url) {
        return new Promise((resolve, reject) => {
            if (window.DocSpace || scriptTag) return resolve();
            docSpaceUrl += docSpaceUrl.endsWith("/") ? "" : "/"
            scriptTag = document.createElement("script");
            scriptTag.src = docSpaceUrl + "static/scripts/api.js";
            scriptTag.async = true;
            document.body.appendChild(scriptTag);

            scriptTag.addEventListener('load', () => {
                return resolve();
            })
            scriptTag.addEventListener('error', () => {
                scriptTag.remove();
                scriptTag = null;
                return reject();
            })
        });
    };

    window.DocSpaceComponent.generateId = function () {
        return Math.floor((1 + Math.random()) * 0x10000000)
            .toString(16)
            .substring(1);
    }

    window.DocSpaceComponent.renderError = function (id, error = { message: DocSpaceComponent.messages.error }) {
        const target = document.getElementById(id);

        let errorDiv = document.createElement('div');
        errorDiv.className="error-stub";

        if (id.includes("selector")) {
            errorDiv.classList.add("selector");
        } else if (id.includes("manager")) {
        } else {
            errorDiv.classList.add("viewer");
        }

        errorDiv.innerHTML = `
            <div class="unavailable-header">
                <img src="${DocSpaceComponent.images.onlyoffice}" />
                <span><b>ONLYOFFICE</b> DocSpace</span>
            </div>
            <img class="unavailable-icon" src="${DocSpaceComponent.images.unavailable}" />
            <div class="unavailable-header-message">${error.header || ""}</div>
            <div class="unavailable-message">${error.message}</div>
        `;

        target.innerHTML = "";
        target.appendChild(errorDiv);
    }

    window.DocSpaceComponent.oodspCredentials = function (hash = null) {
        var xhr = new XMLHttpRequest();

        var body = {};

        if (DocSpaceComponent.isPublic) {
            body.public = true;
        }
        
        if (hash) {
            body.hash = hash;
        }

        xhr.open("POST", DocSpaceComponent.ajaxUrl, false);
        xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
        xhr.send(JSON.stringify(body));

        if (xhr.status === 200) {
            return JSON.parse(xhr.responseText) || null;
        }

        return null;
    }

    window.DocSpaceComponent.initLoginDocSpace = function (frameId, password, onSuccess, onError) {
        DocSpace.SDK.initSystem({
            frameId: frameId,
            events: {
                onAppReady: async function() {
                    const userInfo = await DocSpace.SDK.frames[frameId].getUserInfo();

                    if (userInfo && userInfo.email === DocSpaceComponent.currentUser){
                        onSuccess();
                    } else {
                        var hash = DocSpaceComponent.oodspCredentials();

                        if (hash === null || hash.length === "") {
                            window.location.replace(DocSpaceComponent.loginUrl);
                            return;
                        }

                        DocSpace.SDK.frames[frameId].login(DocSpaceComponent.currentUser, hash)
                            .then(function(response) {
                                if(response.status && response.status !== 200) {
                                    window.location.replace(DocSpaceComponent.loginUrl);
                                }

                                onSuccess();
                            }
                        );
                    }
                },
                onAppError: async function() {
                    onError();
                }
            }
        });
    };

    window.DocSpaceComponent.initPublicDocSpace = function (frameId, width, height, onSuccess, onError) {
        DocSpace.SDK.initFrame({
            frameId: frameId,
            mode: "system",
            width: width,
            height: height,
            events: {
                onAppReady: async function() {
                    const userInfo = await DocSpace.SDK.frames[frameId].getUserInfo();

                    onSuccess();
                    // if (userInfo && userInfo.email === DocSpaceComponent.currentUser) {
                    //     onSuccess();
                    // } else {
                    //     const hash = DocSpaceComponent.oodspCredentials();

                    //     DocSpace.SDK.frames[frameId].login(DocSpaceComponent.currentUser, hash)
                    //         .then(function(response) {
                    //             if(response.status && response.status !== 200) {
                    //                 DocSpace.SDK.frames[frameId].destroyFrame();

                    //                 DocSpaceComponent.renderError(
                    //                     frameId, 
                    //                     {
                    //                         header: DocSpaceComponent.messages.unauthorized_header,
                    //                         message: DocSpaceComponent.messages.unauthorized_message
                    //                     }
                    //                 );
                    //                 return;
                    //             }

                    //             onSuccess();
                    //         }
                    //     );
                    // } 
                },
                onAppError: async function() {
                    onError();
                }
            }
        });
    };

    window.DocSpaceComponent.renderDocSpace = function (frameId, onSuccess) {
        DocSpaceComponent.initScript().then(function() {
            DocSpaceComponent.initLoginDocSpace(
                frameId,
                null,
                onSuccess,
                function() {
                    DocSpaceComponent.renderError(frameId);
                }
            );
        }).catch(function() {
            DocSpaceComponent.renderError(frameId);
        });
    }

    window.DocSpaceComponent.getAbsoluteUrl = function (url) {
        docSpaceUrl = DocSpaceComponent.url.endsWith("/") ? DocSpaceComponent.url.slice(0, -1) : DocSpaceComponent.url;

        if (url.startsWith("http://") || url.startsWith("https://")) {
            var origin = new URL(url).origin;
            url = url.replace(origin, docSpaceUrl);
        } else {
            url = docSpaceUrl + url;
        }

        return url;
    };
})();
