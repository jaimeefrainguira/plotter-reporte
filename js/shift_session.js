const ShiftSession = (() => {
    const COOKIE_NAME = 'plotter_shift_session';
    const COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24; // 24h

    function safeDecode(value) {
        try {
            return decodeURIComponent(value);
        } catch (error) {
            return '';
        }
    }

    function readCookieRaw(name) {
        const cookies = document.cookie ? document.cookie.split('; ') : [];
        for (const row of cookies) {
            const [cookieName, ...rest] = row.split('=');
            if (cookieName === name) {
                return safeDecode(rest.join('='));
            }
        }
        return '';
    }

    function writeCookie(name, value, maxAgeSeconds = COOKIE_MAX_AGE_SECONDS) {
        const encodedValue = encodeURIComponent(value);
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `${name}=${encodedValue}; Max-Age=${maxAgeSeconds}; Path=/; SameSite=Lax${secure}`;
    }

    function clearCookie(name) {
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax${secure}`;
    }

    function getSession() {
        const raw = readCookieRaw(COOKIE_NAME);
        if (!raw) return null;

        try {
            const session = JSON.parse(raw);
            if (!session || typeof session !== 'object') return null;
            const operator = String(session.operator || '').trim();
            const startIso = String(session.startIso || '').trim();
            if (!operator || !startIso) return null;
            return { operator, startIso };
        } catch (error) {
            return null;
        }
    }

    function saveSession(operator, startIso) {
        const payload = JSON.stringify({
            operator: String(operator || '').trim(),
            startIso: String(startIso || '').trim(),
        });
        writeCookie(COOKIE_NAME, payload);
    }

    function clearSession() {
        clearCookie(COOKIE_NAME);
    }

    return {
        getSession,
        saveSession,
        clearSession,
    };
})();
