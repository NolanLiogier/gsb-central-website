(function() {
    var btn = document.getElementById('exportCompanyPdf');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var companyName = document.getElementById('companyName');
        var siret = document.getElementById('siret');
        var siren = document.getElementById('siren');
        var deliveryAddress = document.getElementById('deliveryAddress');
        var sector = document.getElementById('sector');
        var salesman = document.getElementById('salesman');
        var labels = {
            companyName: "Nom de l'entreprise",
            siret: "SIRET",
            siren: "SIREN",
            deliveryAddress: "Adresse de livraison",
            sector: "Secteur d'activité",
            salesman: "Commercial assigné"
        };
        var lines = [];
        if (companyName) lines.push(labels.companyName + " : " + (companyName.value || ""));
        if (siret) lines.push(labels.siret + " : " + (siret.value || ""));
        if (siren) lines.push(labels.siren + " : " + (siren.value || ""));
        if (deliveryAddress) lines.push(labels.deliveryAddress + " : " + (deliveryAddress.value || ""));
        if (sector) lines.push(labels.sector + " : " + (sector.options[sector.selectedIndex] ? sector.options[sector.selectedIndex].text : ""));
        if (salesman) lines.push(labels.salesman + " : " + (salesman.options[salesman.selectedIndex] ? salesman.options[salesman.selectedIndex].text : ""));
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF();
        var y = 20;
        doc.setFontSize(14);
        doc.text("Informations de l'entreprise", 14, y);
        y += 10;
        doc.setFontSize(10);
        for (var i = 0; i < lines.length; i++) {
            doc.text(lines[i], 14, y);
            y += 7;
        }
        var rawName = companyName && companyName.value ? companyName.value.trim() : "";
        var safeName = rawName.replace(/[\/:*?"<>|\\]/g, "").replace(/\s+/g, "-").replace(/-+/g, "-") || "entreprise";
        doc.save(safeName + "-informations.pdf");
    });
})();
