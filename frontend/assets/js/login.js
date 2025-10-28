document.getElementById('login-btn').onclick = async function() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const errorDiv = document.getElementById('login-error');
    errorDiv.style.display = "none";

    if (!username || !password) {
        errorDiv.textContent = "Username dan password wajib diisi!";
        errorDiv.style.display = "block";
        return;
    }
    try {
        const res = await fetch('../backend/api/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ username, password })
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = "index.php";
        } else {
            errorDiv.textContent = data.error || "Login gagal!";
            errorDiv.style.display = "block";
        }
    } catch (err) {
        errorDiv.textContent = "Gagal terhubung ke server!";
        errorDiv.style.display = "block";
    }
};

document.getElementById('password').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('login-btn').click();
});