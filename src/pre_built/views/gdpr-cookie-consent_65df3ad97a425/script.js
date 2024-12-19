let _this = this;

const SETTINGS = {
    class: {
        __ACTIVE: 'cookie-consent--active',
        ACCEPT: 'cookie-consent__button',
        CLOSE: 'cookie-consent__close',
    },
    COOKIE_NAME: '_av-cookie-consent',
};

function setCookie(name, value, expireDays) {
    let date = new Date();
    date.setTime(date.getTime() + (expireDays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
    let cookieArr = document.cookie.split(";");

    for (let i = 0; i < cookieArr.length; i++) {
        let cookiePair = cookieArr[i].split("=");

        if (name === cookiePair[0].trim()) {
            return cookiePair[1];
        }
    }

    return null;
}

function show() {
    /* with CSS animation */
    _this.classList.add(SETTINGS.class.__ACTIVE);
}

function hide() {
    setCookie(SETTINGS.COOKIE_NAME, '1', 365);

    /* hide the element, without removing (as it won't be smooth)
    element will be excluded from DOM since the next page load */
    _this.classList.remove(SETTINGS.class.__ACTIVE);
}

function init() {
    if (getCookie(SETTINGS.COOKIE_NAME)) {
        /* remove the element from the DOM.
         It can't be done on the server side due to server caching */
        _this.remove();
        return;
    }

    _this.querySelector('.' + SETTINGS.class.ACCEPT).addEventListener('click', hide);
    _this.querySelector('.' + SETTINGS.class.CLOSE).addEventListener('click', hide);

    setTimeout(show, 5000);
}

init();