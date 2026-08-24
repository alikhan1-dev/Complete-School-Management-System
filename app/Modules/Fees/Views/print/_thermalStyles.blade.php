{{-- Shared thermal receipt CSS (CI print/thermalPrint*) --}}
<style>
    .page-break { display: block; page-break-before: always; }
    * { padding: 0; margin: 0; }
    body { margin: 0; padding: 0; font-family: Arial, sans-serif; font-size: 11pt; }
    @page { size: 2.9in 11in; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    table th, table td { padding-top: 5px; padding-bottom: 5px; font-size: 9pt; vertical-align: top; }
    p { margin-bottom: 5px; }
    h1 { margin: 0; padding: 0; font-size: 16pt; font-weight: bold; }
    .title-around-span { position: relative; text-align: center; padding: 0; }
    .title-around-span:before {
        content: "";
        display: block;
        width: 100%;
        position: absolute;
        left: 0;
        top: 50%;
        border-top: 2px #000 dashed;
    }
    .title-around-span span {
        position: relative;
        z-index: 1;
        padding: 0 5px;
        color: #000;
        font-weight: bold;
        background: #fff;
    }
    @media print { .no-print { display: none !important; } }
</style>
