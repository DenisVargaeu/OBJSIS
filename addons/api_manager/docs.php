<?php
require_once '../../config/db.php';
require_once '../../includes/functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OBJSIS | API Documentation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; padding: 50px; }
        .container { max-width: 900px; margin: auto; background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(255,255,255,0.05); padding: 50px; border-radius: 30px; backdrop-filter: blur(20px); }
        h1 { color: #f97316; font-size: 2.5rem; font-weight: 900; }
        .endpoint { background: rgba(0,0,0,0.2); border-radius: 12px; padding: 20px; margin: 20px 0; border-left: 4px solid #f97316; }
        code { background: rgba(249,115,22,0.1); color: #fb923c; padding: 3px 8px; border-radius: 6px; }
        pre { background: #000; padding: 20px; border-radius: 12px; overflow-x: auto; color: #00ff41; font-family: monospace; }
        .badge { background: #f97316; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>API Documentation</h1>
        <p>Use your API Master Key to authenticate. All requests should include the key in the URL parameter <code>key</code> or the header <code>X-API-KEY</code>.</p>
        
        <div class="endpoint">
            <h3><span class="badge">GET</span> Get Daily Stats</h3>
            <code>external_api.php?action=stats</code>
            <p>Returns daily revenue and total order count.</p>
            <pre>{
  "success": true,
  "data": { "revenue": "1240.50", "orders": 42 }
}</pre>
        </div>

        <div class="endpoint">
            <h3><span class="badge">GET</span> Get Active Orders</h3>
            <code>external_api.php?action=active_orders</code>
            <p>Returns a list of all currently active (non-paid) orders.</p>
            <pre>{
  "success": true,
  "orders": [
    { "id": 105, "table_number": 12, "status": "preparing", "total_price": "24.99" }
  ]
}</pre>
        </div>
    </div>
</body>
</html>
