<?php
require_once 'config.php';
require_once 'auth.php';

if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BILLA_FX - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0a0c10 0%, #11161f 100%); }
        .glass-card { background: rgba(17, 24, 39, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(59,130,246,0.2); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full glass-card rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span class="text-white text-3xl font-bold">B</span>
            </div>
            <h1 class="text-2xl font-bold text-white">BILLA_FX</h1>
            <p class="text-gray-400 mt-1">ICT A+ Trade Confirmation System</p>
        </div>

        <div class="flex gap-3 mb-6">
            <button id="loginTabBtn" class="flex-1 py-2 rounded-xl font-semibold transition bg-blue-600 text-white">Login</button>
            <button id="registerTabBtn" class="flex-1 py-2 rounded-xl font-semibold transition bg-gray-800 text-gray-400 hover:bg-gray-700">Sign Up</button>
        </div>

        <!-- Login Form -->
        <div id="loginForm" class="space-y-4">
            <div>
                <label class="block text-gray-400 text-sm mb-1">Email Address</label>
                <input type="email" id="loginEmail" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="your@email.com">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Password</label>
                <input type="password" id="loginPassword" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="••••••••">
            </div>
            <button id="doLogin" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition">Sign In</button>
        </div>

        <!-- Register Form (hidden initially) -->
        <div id="registerForm" class="space-y-4 hidden">
            <div>
                <label class="block text-gray-400 text-sm mb-1">Full Name</label>
                <input type="text" id="regFullName" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="John Doe">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Username</label>
                <input type="text" id="regUsername" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="johndoe">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Email</label>
                <input type="email" id="regEmail" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="john@example.com">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Password</label>
                <input type="password" id="regPassword" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-gray-400 text-sm mb-1">Confirm Password</label>
                <input type="password" id="regConfirmPassword" class="w-full bg-gray-800 border border-gray-700 rounded-xl p-3 text-white focus:outline-none focus:border-blue-500" placeholder="••••••••">
            </div>
            <button id="doRegister" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition">Create Account</button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const loginTab = document.getElementById('loginTabBtn');
        const registerTab = document.getElementById('registerTabBtn');
        const loginDiv = document.getElementById('loginForm');
        const registerDiv = document.getElementById('registerForm');

        loginTab.addEventListener('click', () => {
            loginTab.classList.add('bg-blue-600', 'text-white');
            loginTab.classList.remove('bg-gray-800', 'text-gray-400');
            registerTab.classList.remove('bg-blue-600', 'text-white');
            registerTab.classList.add('bg-gray-800', 'text-gray-400');
            loginDiv.classList.remove('hidden');
            registerDiv.classList.add('hidden');
        });

        registerTab.addEventListener('click', () => {
            registerTab.classList.add('bg-blue-600', 'text-white');
            registerTab.classList.remove('bg-gray-800', 'text-gray-400');
            loginTab.classList.remove('bg-blue-600', 'text-white');
            loginTab.classList.add('bg-gray-800', 'text-gray-400');
            registerDiv.classList.remove('hidden');
            loginDiv.classList.add('hidden');
        });

        $('#doLogin').click(function() {
            $.ajax({
                url: 'auth.php',
                method: 'POST',
                data: {
                    action: 'login',
                    email: $('#loginEmail').val(),
                    password: $('#loginPassword').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'dashboard.php';
                    } else {
                        alert(response.message);
                    }
                }
            });
        });

        $('#doRegister').click(function() {
            if ($('#regPassword').val() !== $('#regConfirmPassword').val()) {
                alert('Passwords do not match');
                return;
            }
            $.ajax({
                url: 'auth.php',
                method: 'POST',
                data: {
                    action: 'register',
                    username: $('#regUsername').val(),
                    email: $('#regEmail').val(),
                    password: $('#regPassword').val(),
                    full_name: $('#regFullName').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'dashboard.php';
                    } else {
                        alert(response.message);
                    }
                }
            });
        });
    </script>
</body>
</html>