<?php
// addons/multi_currency/hooks.php

$currency_html = '
<div class="nav-item" style="margin-right:15px; display:flex; align-items:center;">
    <select id="currencySelector" class="theme-toggle" style="background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:var(--text-main); padding:5px 10px; border-radius:10px; font-size:0.8rem; font-weight:700; cursor:pointer; height:38px;">
        <option value="EUR">€ EUR</option>
        <option value="USD">$ USD</option>
        <option value="GBP">£ GBP</option>
        <option value="HUF">Ft HUF</option>
    </select>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const selector = document.getElementById("currencySelector");
    
    let rates = { "EUR": 1.0, "USD": 1.08, "GBP": 0.85, "HUF": 395.5 };
    const symbols = { "EUR": "€", "USD": "$", "GBP": "£", "HUF": "Ft" };

    async function fetchRates() {
        try {
            const res = await fetch("../api/addons_api.php?action=currency");
            const data = await res.json();
            if (data.success) rates = { "EUR": 1.0, ...data.rates };
        } catch(e) {}
    }

    function updatePrices() {
        const currency = selector.value;
        const rate = rates[currency] || 1.0;
        const symbol = symbols[currency] || "€";
        
        localStorage.setItem("objsis_currency", currency);

        // Brute-force price detection with better regex
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null, false);
        let node;
        while(node = walker.nextNode()) {
            const text = node.nodeValue;
            if (text.includes("€")) {
                // If it looks like a price (e.g. 12,00 € or 12.00€)
                if (text.match(/[\d.,]+\s*€/)) {
                    if (!node.originalValue) node.originalValue = text;
                    const origNumeric = parseFloat(node.originalValue.replace(/[^\d.,]/g, "").replace(",", "."));
                    if (!isNaN(origNumeric)) {
                        const converted = (origNumeric * rate).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        node.nodeValue = node.originalValue.replace(/[\d.,]+\s*€/, converted + " " + symbol);
                    }
                }
            }
        }
    }

    selector.onchange = updatePrices;

    // Initial load
    fetchRates().then(() => {
        const saved = localStorage.getItem("objsis_currency");
        if (saved && rates[saved]) selector.value = saved;
        updatePrices();
    });
    
    // Observer for dynamic content
    const observer = new MutationObserver(() => {
        clearTimeout(window.currencyTimeout);
        window.currencyTimeout = setTimeout(updatePrices, 300);
    });
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
});
</script>
';

$GLOBALS['addon_navbar_items'][] = $currency_html;
?>
