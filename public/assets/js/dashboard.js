/**
 * Initialise les graphiques du tableau de bord selon le type d'utilisateur.
 * 
 * Gère trois types de graphiques :
 * - Graphique d'évolution du CA pour les commerciaux (ligne)
 * - Graphique des produits les plus commandés pour les clients (barres)
 * - Graphique de rotation du stock pour les logisticiens (barres)
 * 
 * Récupère les données depuis les attributs data- des canvas et crée les graphiques Chart.js.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Vérification que Chart.js est disponible
    if (typeof Chart === 'undefined') {
        console.error('Chart.js n\'est pas chargé');
        return;
    }

    // Graphique d'évolution du CA (commercial)
    const revenueCtx = document.getElementById('revenueEvolutionChart');
    if (revenueCtx) {
        const evolutionMonths = JSON.parse(revenueCtx.getAttribute('data-months') || '[]');
        const evolutionValues = JSON.parse(revenueCtx.getAttribute('data-values') || '[]');
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: evolutionMonths,
                datasets: [{
                    label: 'Chiffre d\'affaires (€)',
                    data: evolutionValues,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + parseFloat(context.parsed.y).toFixed(2) + ' €';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' €';
                            }
                        }
                    }
                }
            }
        });
    }

    // Graphique des produits les plus commandés (client)
    const productsCtx = document.getElementById('clientProductsChart');
    if (productsCtx) {
        const productsLabels = JSON.parse(productsCtx.getAttribute('data-labels') || '[]');
        const productsData = JSON.parse(productsCtx.getAttribute('data-values') || '[]');
        
        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: productsLabels,
                datasets: [{
                    label: 'Quantité commandée',
                    data: productsData,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Graphique de rotation du stock (logisticien)
    const stockRotationCtx = document.getElementById('stockRotationChart');
    if (stockRotationCtx) {
        const rotationLabels = JSON.parse(stockRotationCtx.getAttribute('data-labels') || '[]');
        const rotationCounts = JSON.parse(stockRotationCtx.getAttribute('data-values') || '[]');
        
        new Chart(stockRotationCtx, {
            type: 'bar',
            data: {
                labels: rotationLabels,
                datasets: [{
                    label: 'Nombre de sorties',
                    data: rotationCounts,
                    backgroundColor: 'rgba(20, 184, 166, 0.5)',
                    borderColor: 'rgba(20, 184, 166, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' sortie(s)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});

