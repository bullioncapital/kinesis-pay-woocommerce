const kpay_settings = window.wc.wcSettings.getSetting('kinesis_pay_gateway_data', {});
const kpay_icon = kpay_checkout_data.icon ? kpay_checkout_data.icon : '';
const kpay_label = window.wp.htmlEntities.decodeEntities(kpay_settings.title) || window.wp.i18n.__('Kinesis Pay Gateway', 'kinesis-pay-gateway');
const Kpay_Content = () => {
    return window.wp.htmlEntities.decodeEntities(kpay_checkout_data.description || '');
};
const Kpay_Block_Gateway = {
    name: 'kinesis_pay_gateway',
    label: kpay_icon ? window.wp.element.createElement(() =>
        window.wp.element.createElement(
          "span",
          null,
          window.wp.element.createElement("img", {
            id: "kpay-payment-method-logo",
            src: kpay_icon,
            alt: kpay_label,
            style: {
              width: "200px",
              height: "auto",
              maxHeight: "50px",
            },
          }),
        )
      ) : kpay_label,
    content: Object(window.wp.element.createElement)(Kpay_Content, null),
    edit: Object(window.wp.element.createElement)(Kpay_Content, null),
    canMakePayment: () => true,
    ariaLabel: kpay_label,
    supports: {
        features: kpay_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Kpay_Block_Gateway);
