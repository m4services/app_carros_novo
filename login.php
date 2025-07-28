<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

$page_title = 'Login';

try {
    require_once 'includes/header.php';
} catch (Exception $e) {
    error_log('Erro ao carregar sistema: ' . $e->getMessage());
    die('Erro ao carregar sistema. Verifique as configurações.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $lembrar = isset($_POST['lembrar']);
        
        if (empty($email) || empty($senha)) {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            try {
                if (isset($auth) && $auth->login($email, $senha, $lembrar)) {
                    redirect('/dashboard.php');
                } else {
                    $error = 'Email ou senha incorretos.';
                }
            } catch (Exception $e) {
                error_log('Erro no login: ' . $e->getMessage());
                $error = 'Erro interno. Tente novamente.';
            }
        }
    }
}

// Se já está logado, redirecionar
try {
    if (isset($auth) && $auth->isLoggedIn()) {
        redirect('/dashboard.php');
    }
} catch (Exception $e) {
    error_log('Erro ao verificar login: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($config['nome_empresa']) ?> - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .glass-effect {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .slide-in {
            animation: slideIn 0.8s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Desktop Layout -->
    <div class="hidden lg:flex min-h-screen">
        <!-- Left Side - Branding -->
        <div class="flex-1 gradient-bg relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full floating-animation"></div>
                <div class="absolute top-40 right-20 w-20 h-20 bg-white rounded-full floating-animation" style="animation-delay: -2s;"></div>
                <div class="absolute bottom-20 left-20 w-24 h-24 bg-white rounded-full floating-animation" style="animation-delay: -4s;"></div>
                <div class="absolute bottom-40 right-10 w-16 h-16 bg-white rounded-full floating-animation" style="animation-delay: -1s;"></div>
            </div>
            
            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center items-center h-full px-12 text-white">
                <div class="text-center slide-in">
                    <?php if ($config['logo']): ?>
                        <div class="mb-8">
                            <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" 
                                 alt="Logo" 
                                 class="h-20 mx-auto filter brightness-0 invert">
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="text-5xl font-bold mb-6 leading-tight">
                        <?= escape($config['nome_empresa']) ?>
                    </h1>
                    
                    <p class="text-xl opacity-90 mb-8 max-w-md leading-relaxed">
                        Sistema completo para controle de veículos, manutenções e deslocamentos.
                    </p>
                    
                    <div class="flex items-center justify-center space-x-8 text-sm opacity-80">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Controle de Frota</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span>Relatórios</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Seguro</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="flex-1 bg-white flex items-center justify-center px-12">
            <div class="w-full max-w-md slide-in">
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Bem-vindo de volta</h2>
                    <p class="text-gray-600">Entre com suas credenciais para acessar o sistema</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <p class="text-red-800"><?= escape($error) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input type="email" name="email" id="email" required
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="seu@email.com"
                                   value="<?= escape($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div>
                        <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input type="password" name="senha" id="senha" required
                                   class="block w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                   placeholder="Sua senha">
                            <button type="button" onclick="togglePassword()" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" id="toggleIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input type="checkbox" name="lembrar" id="lembrar" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="lembrar" class="ml-2 block text-sm text-gray-700">
                                Manter conectado
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-600 hover:to-purple-700 focus:ring-4 focus:ring-blue-200 transition-all duration-200 transform hover:scale-[1.02]">
                        Entrar no Sistema
                    </button>
                </form>
                
                <div class="mt-8 text-center">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-sm text-gray-600 mb-2">Credenciais de demonstração:</p>
                        <div class="text-xs text-gray-500 space-y-1">
                            <div><strong>Email:</strong> admin@sistema.com</div>
                            <div><strong>Senha:</strong> admin123</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile Layout -->
    <div class="lg:hidden min-h-screen gradient-bg relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white rounded-full floating-animation"></div>
            <div class="absolute top-32 right-8 w-12 h-12 bg-white rounded-full floating-animation" style="animation-delay: -2s;"></div>
            <div class="absolute bottom-32 left-8 w-16 h-16 bg-white rounded-full floating-animation" style="animation-delay: -4s;"></div>
        </div>
        
        <div class="relative z-10 flex flex-col min-h-screen">
            <!-- Header -->
            <div class="text-center pt-12 pb-8 px-6 text-white">
                <?php if ($config['logo']): ?>
                    <div class="mb-6">
                        <img src="<?= UPLOADS_URL ?>/logos/<?= escape($config['logo']) ?>" 
                             alt="Logo" 
                             class="h-16 mx-auto filter brightness-0 invert">
                    </div>
                <?php endif; ?>
                
                <h1 class="text-3xl font-bold mb-3">
                    <?= escape($config['nome_empresa']) ?>
                </h1>
                
                <p class="text-lg opacity-90 max-w-sm mx-auto">
                    Sistema de controle de veículos
                </p>
            </div>
            
            <!-- Login Form -->
            <div class="flex-1 px-6 pb-8">
                <div class="glass-effect rounded-2xl p-6 slide-in">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-white mb-2">Entrar</h2>
                        <p class="text-white opacity-80">Acesse sua conta</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-500 bg-opacity-20 border border-red-300 rounded-lg p-3 mb-4">
                            <p class="text-white text-sm"><?= escape($error) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div>
                            <label for="email_mobile" class="block text-sm font-medium text-white mb-2">Email</label>
                            <input type="email" name="email" id="email_mobile" required
                                   class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-70 focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-white"
                                   placeholder="seu@email.com"
                                   value="<?= escape($_POST['email'] ?? '') ?>">
                        </div>
                        
                        <div>
                            <label for="senha_mobile" class="block text-sm font-medium text-white mb-2">Senha</label>
                            <div class="relative">
                                <input type="password" name="senha" id="senha_mobile" required
                                       class="w-full px-4 py-3 bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white placeholder-white placeholder-opacity-70 focus:ring-2 focus:ring-white focus:ring-opacity-50 focus:border-white pr-12"
                                       placeholder="Sua senha">
                                <button type="button" onclick="togglePasswordMobile()" 
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="w-5 h-5 text-white opacity-70" id="toggleIconMobile" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="lembrar" id="lembrar_mobile" 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="lembrar_mobile" class="ml-2 block text-sm text-white">
                                Manter conectado
                            </label>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-white text-gray-900 py-3 px-4 rounded-lg font-medium hover:bg-gray-100 focus:ring-4 focus:ring-white focus:ring-opacity-50 transition-all duration-200 transform hover:scale-[1.02]">
                            Entrar no Sistema
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <div class="bg-white bg-opacity-10 rounded-lg p-3">
                            <p class="text-white text-xs mb-2">Demonstração:</p>
                            <div class="text-xs text-white opacity-80 space-y-1">
                                <div>admin@sistema.com</div>
                                <div>admin123</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                senhaInput.type = 'password';
                toggleIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        function togglePasswordMobile() {
            const senhaInput = document.getElementById('senha_mobile');
            const toggleIcon = document.getElementById('toggleIconMobile');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                senhaInput.type = 'password';
                toggleIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
        
        // Auto-focus no campo email
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email') || document.getElementById('email_mobile');
            if (emailField) {
                emailField.focus();
            }
        });
    </script>
</body>
</html>