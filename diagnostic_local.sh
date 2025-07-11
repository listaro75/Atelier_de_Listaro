#!/bin/bash
# Script de diagnostic immédiat pour Raspberry Pi
# À exécuter directement sur le Raspberry Pi

echo "🚨 DIAGNOSTIC URGENT - Atelier de Listaro"
echo "========================================="
echo "Date: $(date)"
echo ""

echo "1️⃣ ÉTAT DES SERVICES"
echo "==================="
for service in apache2 networking ssh postfix; do
    status=$(systemctl is-active $service 2>/dev/null)
    if [ "$status" = "active" ]; then
        echo "✅ $service : Actif"
    else
        echo "❌ $service : $status"
    fi
done
echo ""

echo "2️⃣ CONNECTIVITÉ RÉSEAU"
echo "======================"
echo "🌐 IP publique actuelle :"
PUBLIC_IP=$(curl -s --connect-timeout 10 ifconfig.me 2>/dev/null || echo "Non accessible")
echo "   $PUBLIC_IP"
echo ""

echo "🏠 IP locale :"
ip addr show | grep "inet " | grep -v "127.0.0.1" | while read line; do
    echo "   $line"
done
echo ""

echo "3️⃣ PORTS ET SERVICES"
echo "==================="
echo "🔌 Ports en écoute :"
netstat -tlnp 2>/dev/null | grep -E ":80|:443|:22" | head -5
echo ""

echo "4️⃣ TEST APACHE LOCAL"
echo "==================="
if curl -s --connect-timeout 5 http://localhost >/dev/null 2>&1; then
    echo "✅ Apache répond localement"
else
    echo "❌ Apache ne répond pas localement"
fi
echo ""

echo "5️⃣ TEST INTERNET"
echo "================"
if ping -c 2 8.8.8.8 >/dev/null 2>&1; then
    echo "✅ Accès internet OK"
else
    echo "❌ Pas d'accès internet"
fi
echo ""

echo "🛠️ RÉPARATIONS RAPIDES"
echo "======================="

# Redémarrer Apache si nécessaire
if ! systemctl is-active --quiet apache2; then
    echo "🔄 Redémarrage d'Apache..."
    sudo systemctl restart apache2
    sleep 3
    if systemctl is-active --quiet apache2; then
        echo "✅ Apache redémarré avec succès"
    else
        echo "❌ Échec du redémarrage d'Apache"
    fi
fi

# Test final
echo ""
echo "📊 RÉSUMÉ FINAL"
echo "==============="
echo "Hostname: $(hostname)"
echo "IP locale: $(ip route get 1 | awk '{print $7}' | head -1)"
echo "IP publique: $(curl -s --connect-timeout 5 ifconfig.me || echo 'Non accessible')"
echo "Apache: $(systemctl is-active apache2)"
echo ""

# URLs d'accès
echo "🔗 URLs D'ACCÈS POSSIBLES"
echo "========================="
LOCAL_IP=$(ip route get 1 | awk '{print $7}' | head -1)
echo "Accès local: http://$LOCAL_IP"
echo "Accès localhost: http://localhost"

# Si IP publique accessible
if [ "$PUBLIC_IP" != "Non accessible" ] && [ "$PUBLIC_IP" != "" ]; then
    echo "Accès public: http://$PUBLIC_IP"
else
    echo "⚠️ Accès public indisponible"
fi

echo ""
echo "✅ Diagnostic terminé. Utilisez ces informations pour dépanner."
