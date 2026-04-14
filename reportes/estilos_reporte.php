<?php
/**
 * Estilos comunes para los reportes PDF
 */
$estilos_reporte = "
    body {
        font-family: 'Helvetica', Arial, sans-serif;
        color: #333;
        margin: 0;
        padding: 0;
        font-size: 12px;
    }
    .header {
        text-align: center;
        border-bottom: 2px solid #6bcb77;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .clinic-name {
        color: #1f3e4b;
        font-size: 24px;
        font-weight: bold;
        margin: 0;
    }
    .report-title {
        color: #6bcb77;
        font-size: 18px;
        margin: 5px 0;
    }
    .date-info {
        color: #7f8c8d;
        font-size: 11px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    th {
        background-color: #f8fbfe;
        color: #2c5a6e;
        font-weight: bold;
        text-align: left;
        padding: 10px;
        border-bottom: 2px solid #eef2f8;
        text-transform: uppercase;
        font-size: 10px;
    }
    td {
        padding: 8px 10px;
        border-bottom: 1px solid #f0f4f9;
        vertical-align: middle;
    }
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 10px;
        color: #95a5a6;
        border-top: 1px solid #eef2f8;
        padding-top: 10px;
    }
    .page-number:after { content: counter(page); }
    
    /* Utilidades */
    .align-right { text-align: right; }
    .align-center { text-align: center; }
    .badge {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 9px;
        font-weight: bold;
    }
    .badge-primary { background: #e0f0ff; color: #1f6e8c; }
    .badge-success { background: #e8f0e8; color: #2d6a4f; }
    .badge-warning { background: #fff0db; color: #c97e00; }
    .badge-danger { background: #fee9e7; color: #e74c3c; }
    
    .total-box {
        background: #f8fbfe;
        border: 1px solid #eef2f8;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
        text-align: right;
    }
    .total-label { color: #2c5a6e; font-weight: bold; }
    .total-amount { color: #1f3e4b; font-size: 16px; font-weight: bold; }
";
