@font-face {
    font-family: 'Roboto Mono Label';
    src: url('{{ $fontRegularUrl }}') format('truetype');
    font-style: normal;
    font-weight: 400;
}
@font-face {
    font-family: 'Roboto Mono Label';
    src: url('{{ $fontBoldUrl }}') format('truetype');
    font-style: normal;
    font-weight: 700;
}

.product-label-canvas,
.product-label-canvas * {
    box-sizing: border-box;
}

.product-label-canvas {
    position: relative;
    width: 288pt;
    height: 216pt;
    overflow: hidden;
    background: #fff;
    color: #202124;
    font-family: 'Roboto Mono Label', 'DejaVu Sans Mono', monospace;
    line-height: 1;
}

.label-category-badge {
    position: absolute;
    left: 21.289pt;
    top: 34.430pt;
    width: 64.835pt;
    height: 27.525pt;
    border-radius: 4.25pt;
    background: #202124;
    color: #fff;
    display: block;
    text-align: center;
    overflow: hidden;
}

.label-category-badge > span {
    display: block;
    position: absolute;
    left: -4pt;
    width: 64.835pt;
    padding: 0 3pt;
    line-height: 1;
    white-space: nowrap;
}

.label-rule {
    position: absolute;
    background: #202124;
}

.label-rule-top {
    left: 21.032pt;
    top: 93.681pt;
    width: 143.061pt;
    height: 1.5pt;
}

.label-rule-middle {
    left: 21.032pt;
    top: 120.819pt;
    width: 143.061pt;
    height: 1.5pt;
}

.label-rule-vertical {
    left: 164.10pt;
    top: 8.6pt;
    width: 1.5pt;
    height: 173.1pt;
    transform: rotate(0.20deg);
    transform-origin: top center;
}

.label-rule-bottom {
    left: 21.6pt;
    top: 181.013pt;
    width: 244.8pt;
    height: 1.5pt;
}

.label-country-heading {
    position: absolute;
    left: 183.692pt;
    top: 32.2pt;
    font-size: 8pt;
    white-space: nowrap;
}

.label-country-value {
    position: absolute;
    right: 23.089pt;
    top: 43.2pt;
    width: 101pt;
    text-align: right;
    font-weight: 700;
    letter-spacing: .4pt;
    white-space: nowrap;
}

.label-product-name {
    position: absolute;
    left: 21.6pt;
    top: 101.1pt;
    width: 142pt;
    letter-spacing: .54pt;
    white-space: nowrap;
    overflow: hidden;
}

.label-price-line {
    position: absolute;
    left: 21.6pt;
    top: 159.4pt;
    width: 141pt;
    white-space: nowrap;
    overflow: hidden;
}

.label-logo {
    position: absolute;
    left: 209.601pt;
    top: 98.005pt;
    width: 63.75pt;
    height: 63.75pt;
}

.label-company-name {
    position: absolute;
    left: 198.963pt;
    top: 156.6pt;
    width: 66pt;
    text-align: center;
    white-space: nowrap;
}

.label-fssai {
    position: absolute;
    left: 198.963pt;
    top: 169.2pt;
    width: 66pt;
    font-size: 5pt;
    text-align: center;
    white-space: nowrap;
}

.label-best-before {
    position: absolute;
    left: 21.032pt;
    top: 188.2pt;
    width: 145pt;
    font-size: 6pt;
    white-space: nowrap;
}

.label-website {
    position: absolute;
    right: 22.327pt;
    top: 186.2pt;
    width: 105pt;
    text-align: right;
    white-space: nowrap;
}
