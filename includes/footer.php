<?php if (isset($auth) && $auth->isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>
    
    <!-- Custom JavaScript -->
    <script>
        // Toggle sidebar (mobile)
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Close sidebar when clicking outside (mobile)
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Função para mostrar loading
        function showLoading(element) {
            const loading = element.querySelector('.loading');
            if (loading) {
                loading.classList.add('show');
            }
            element.disabled = true;
            element.style.opacity = '0.7';
        }
        
        // Função para esconder loading
        function hideLoading(element) {
            const loading = element.querySelector('.loading');
            if (loading) {
                loading.classList.remove('show');
            }
            element.disabled = false;
            element.style.opacity = '1';
        }
        
        // Confirmar exclusões
        function confirmDelete(message = 'Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.') {
            return confirm('⚠️ ATENÇÃO\n\n' + message);
        }
        
        // Preview de imagens
        function previewImage(input, previewElement) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewElement.src = e.target.result;
                    previewElement.style.display = 'block';
                    previewElement.style.animation = 'fadeInUp 0.5s ease';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Função para formatar números
        function formatNumber(num) {
            return new Intl.NumberFormat('pt-BR').format(num);
        }
        
        // Função para formatar moeda
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }
        
        // Função para mostrar toast notifications
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${getToastIcon(type)} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }
        
        function getToastIcon(type) {
            const icons = {
                'success': 'check-circle',
                'danger': 'exclamation-triangle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            return icons[type] || 'info-circle';
        }
        
        // Validação de formulários
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                            
                            // Focar no primeiro campo inválido
                            const firstInvalid = form.querySelector(':invalid');
                            if (firstInvalid) {
                                firstInvalid.focus();
                                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        } else {
                            // Mostrar loading no botão de submit
                            const submitBtn = form.querySelector('button[type="submit"]');
                            if (submitBtn) {
                                showLoading(submitBtn);
                            }
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    if (alert) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 7000);
            });
            
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Inicializar popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Adicionar efeitos de hover em cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('no-hover')) {
                        this.style.transform = 'translateY(-2px)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('no-hover')) {
                        this.style.transform = 'translateY(0)';
                    }
                });
            });
            
            // Adicionar animações de entrada
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            // Observar elementos com animação
            const animatedElements = document.querySelectorAll('.animate-on-scroll');
            animatedElements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });
            
            // Melhorar acessibilidade
            const buttons = document.querySelectorAll('button, .btn');
            buttons.forEach(btn => {
                if (!btn.getAttribute('aria-label') && !btn.textContent.trim()) {
                    const icon = btn.querySelector('i[class*="bi-"]');
                    if (icon) {
                        const iconClass = Array.from(icon.classList).find(cls => cls.startsWith('bi-'));
                        if (iconClass) {
                            btn.setAttribute('aria-label', iconClass.replace('bi-', '').replace('-', ' '));
                        }
                    }
                }
            });
            
            // Tornar funções globais
            window.showToast = showToast;
            
            // Carregar notificações se estiver logado
            <?php if (isset($auth) && $auth->isLoggedIn()): ?>
            loadNotifications();
            
            // Atualizar notificações a cada 60 segundos
            setInterval(loadNotifications, 60000);
            <?php endif; ?>
        });
        
        // Funções de notificação
        function loadNotifications() {
            fetch('/api/notifications.php?action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadges(data.count);
                    }
                })
                .catch(error => console.error('Erro ao carregar notificações:', error));
        }
        
        function updateNotificationBadges(count) {
            const badges = [
                'notification-badge',
                'user-notification-badge', 
                'dropdown-notification-badge'
            ];
            
            badges.forEach(badgeId => {
                const badge = document.getElementById(badgeId);
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            });
        }
        
        function showNotifications() {
            const modal = new bootstrap.Modal(document.getElementById('notificationsModal'));
            modal.show();
            
            fetch('/api/notifications.php?action=get')
                .then(response => response.json())
                .then(data => {
                    const content = document.getElementById('notifications-content');
                    
                    if (data.success && data.notifications.length > 0) {
                        let html = '<div class="list-group list-group-flush">';
                        
                        data.notifications.forEach(notification => {
                            const typeIcons = {
                                'info': 'bi-info-circle text-info',
                                'warning': 'bi-exclamation-triangle text-warning',
                                'success': 'bi-check-circle text-success',
                                'danger': 'bi-exclamation-triangle text-danger'
                            };
                            
                            const icon = typeIcons[notification.tipo] || 'bi-info-circle text-info';
                            const date = new Date(notification.created_at).toLocaleString('pt-BR');
                            
                            html += `
                                <div class="list-group-item list-group-item-action" onclick="markNotificationRead(${notification.id})">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="bi ${icon} me-2"></i>
                                            ${notification.titulo}
                                        </h6>
                                        <small>${date}</small>
                                    </div>
                                    <p class="mb-1">${notification.mensagem}</p>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        content.innerHTML = html;
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-4">
                                <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Nenhuma notificação</h5>
                                <p class="text-muted">Você está em dia com tudo!</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar notificações:', error);
                    document.getElementById('notifications-content').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Erro ao carregar notificações
                        </div>
                    `;
                });
        }
        
        function markNotificationRead(id) {
            fetch('/api/notifications.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                }
            })
            .catch(error => console.error('Erro ao marcar notificação como lida:', error));
        }
        
        function markAllNotificationsRead() {
            fetch('/api/notifications.php?action=mark_all_read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    showNotifications(); // Recarregar modal
                    showToast('Todas as notificações foram marcadas como lidas', 'success');
                }
            })
            .catch(error => console.error('Erro ao marcar todas as notificações como lidas:', error));
        }
    </script>
</body>
</html>