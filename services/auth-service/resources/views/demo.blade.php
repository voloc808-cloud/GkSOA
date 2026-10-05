<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumen API Lab</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #17233b; background: #f1f5fa; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { max-width: 1050px; margin: auto; padding: 48px 24px; }
        .eyebrow { color: #315bb0; font-size: 12px; font-weight: 800; letter-spacing: 2px; }
        h1 { font-size: clamp(28px, 5vw, 42px); margin: 12px 0; }
        .intro, .hint { color: #52627a; line-height: 1.6; }
        .grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 24px; margin-top: 28px; }
        section { background: white; border: 1px solid #dce4ef; border-radius: 18px; padding: 26px; }
        h2 { font-size: 19px; margin: 0 0 20px; }
        label { display: block; font-weight: 600; margin: 18px 0 8px; }
        input, textarea { width: 100%; border: 1px solid #bac8da; border-radius: 9px; padding: 12px; font: inherit; }
        textarea { font: 12px/1.6 monospace; resize: vertical; }
        button { border: 0; border-radius: 9px; padding: 12px 16px; font: inherit; font-weight: 600; cursor: pointer; background: #2456cd; color: white; }
        button:disabled { opacity: .5; cursor: not-allowed; }
        button.secondary { color: #214da7; background: #eaf0fd; }
        button:focus-visible, input:focus-visible, textarea:focus-visible { outline: 3px solid #76a9ff; outline-offset: 3px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
        .hint { font-size: 13px; }
        .result { margin-top: 24px; }
        pre { background: #132138; color: #d7e6ff; padding: 20px; border-radius: 10px; overflow-wrap: anywhere; white-space: pre-wrap; max-height: 420px; overflow: auto; font-size: 13px; line-height: 1.6; }
        #status { min-height: 24px; font-weight: 600; color: #315bb0; }
        #status.error { color: #b32632; }
        @media (max-width: 700px) { .grid { grid-template-columns: 1fr; } main { padding: 28px 16px; } section { padding: 20px; } }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">LARAVEL / LUMEN · RESTFUL API</div>
    <h1>Lumen API Lab</h1>
    <p class="intro">Đăng nhập, xem JWT và gọi API được bảo vệ — ngay trên trình duyệt.</p>
    <div class="grid">
        <section>
            <h2>1. Đăng nhập</h2>
            <form id="login-form">
                <label for="username">Tên đăng nhập</label>
                <input id="username" name="userName" value="student" autocomplete="username" required maxlength="255">
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <p class="hint">Tài khoản thực hành: <strong>student / 123456</strong>. Nhập mật khẩu gốc; trang sẽ chuyển sang Base64 trước khi gửi.</p>
                <button type="submit" id="login">Đăng nhập</button>
            </form>
        </section>
        <section>
            <h2>2. Thử API với JWT</h2>
            <label for="token">Access token</label>
            <textarea id="token" rows="5" readonly placeholder="JWT sẽ xuất hiện sau khi đăng nhập thành công."></textarea>
            <div class="actions">
                <button id="auth" disabled>Xác thực /auth</button>
                <button id="hello" disabled>Hello World</button>
                <button id="clear" class="secondary" disabled>Xóa token trên trang</button>
            </div>
            <p class="hint">Token chỉ giữ trong bộ nhớ trang và mất khi tải lại. Xóa ở đây không thu hồi token trên server.</p>
        </section>
    </div>
    <section class="result">
        <h2>3. Kết quả API</h2>
        <p id="status" role="status" aria-live="polite">Sẵn sàng. Hãy đăng nhập để bắt đầu.</p>
        <pre id="output">Chưa gửi yêu cầu.</pre>
        <details id="claims-panel">
            <summary>Xem cấu trúc JWT (header và payload)</summary>
            <p class="hint">JWT có ba phần: header.payload.signature. Giải mã chỉ để xem nội dung; API /auth kiểm tra chữ ký và thời hạn.</p>
            <pre id="claims">Chưa có token.</pre>
        </details>
    </section>
</main>
<script>
    'use strict';
    const element = id => document.getElementById(id);
    let accessToken = '';
    let busy = false;

    function updateButtons() {
        element('login').disabled = busy;
        ['auth', 'hello', 'clear'].forEach(id => element(id).disabled = busy || !accessToken);
    }

    function setToken(token) {
        accessToken = token;
        element('token').value = token;
        element('claims').textContent = 'Chưa có token.';
        if (token) {
            try {
                const decode = part => {
                    const base64 = part.replace(/-/g, '+').replace(/_/g, '/');
                    const bytes = Uint8Array.from(atob(base64.padEnd(Math.ceil(base64.length / 4) * 4, '=')), c => c.charCodeAt(0));
                    return JSON.parse(new TextDecoder().decode(bytes));
                };
                const parts = token.split('.');
                element('claims').textContent = JSON.stringify({header: decode(parts[0]), payload: decode(parts[1])}, null, 2);
            } catch {
                element('claims').textContent = 'Không đọc được cấu trúc token.';
            }
        }
        updateButtons();
    }

    async function send(path, body) {
        if (busy) return;
        busy = true;
        const isLogin = body !== undefined;
        if (isLogin) setToken('');
        updateButtons();
        const method = isLogin ? 'POST' : 'GET';
        element('status').className = '';
        element('status').textContent = `Đang gửi ${method} ${path}…`;
        element('output').textContent = 'Đang chờ phản hồi…';
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const headers = {Accept: 'application/json'};
            if (isLogin) headers['Content-Type'] = 'application/json';
            else headers.Authorization = `Bearer ${accessToken}`;
            const response = await fetch(path, {
                method, headers, signal: controller.signal,
                ...(isLogin ? {body: JSON.stringify(body)} : {})
            });
            const text = await response.text();
            let data;
            try { data = JSON.parse(text); } catch { data = {message: 'Server trả về nội dung không phải JSON.', response: text}; }
            element('output').textContent = JSON.stringify(data, null, 2);
            element('status').textContent = `${method} ${path} → HTTP ${response.status}`;
            element('status').className = response.ok ? '' : 'error';
            if (isLogin && response.ok && typeof data.access_token === 'string') {
                setToken(data.access_token);
                element('password').value = '';
            }
            if (response.status === 401) setToken('');
        } catch (error) {
            element('status').className = 'error';
            element('status').textContent = error.name === 'AbortError' ? 'Yêu cầu quá thời gian chờ. Hãy thử lại.' : 'Không kết nối được API. Kiểm tra terminal chạy server.';
            element('output').textContent = 'Không nhận được phản hồi từ server.';
        } finally {
            clearTimeout(timeout);
            busy = false;
            updateButtons();
        }
    }

    element('login-form').addEventListener('submit', event => {
        event.preventDefault();
        const bytes = new TextEncoder().encode(element('password').value);
        const password = btoa(Array.from(bytes, byte => String.fromCharCode(byte)).join(''));
        send('/login', {userName: element('username').value, password});
    });
    element('auth').addEventListener('click', () => send('/auth'));
    element('hello').addEventListener('click', () => send('/hello'));
    element('clear').addEventListener('click', () => {
        setToken('');
        element('output').textContent = 'Chưa gửi yêu cầu.';
        element('status').className = '';
        element('status').textContent = 'Đã xóa token trên trang. Bạn có thể đăng nhập lại.';
    });
</script>
</body>
</html>
