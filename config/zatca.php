<?php


$CONFIG_CNF_FILE_TEMPLATE = "oid_section=OIDS
[ OIDS ]
certificateTemplateName= 1.3.6.1.4.1.311.20.2
[req]
default_bits=2048
req_extensions=v3_req
x509_extensions=v3_Ca
prompt=no
default_md=sha256
req_extensions=req_ext
distinguished_name=dn
[dn]
CN=__commonName
C=__country
serialNumber=__vatNumber
OU=__organizationalUnitName
O=__organizationName
[v3_req]
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
[req_ext]
certificateTemplateName = ASN1:PRINTABLESTRING:ZATCA-code-Signing
subjectAltName = dirName:alt_names
[alt_names]
SN=__serialNumber
UID=__vatNumber
title=__invoiceType
registeredAddress=__registeredAddress
businessCategory=__businessCategory
# [v3_Ca]
# subjectKeyIdentifier=hash
# authorityKeyIdentifier=keyid:always,issue";


$zatcaMode = "production"; // sandbox or simulation or production
if ($zatcaMode == "sandbox") {
    $zatcaBaseUrl = "https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal";
} else if ($zatcaMode == "simulation") {
    $zatcaBaseUrl = "https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation";
} else {
    $zatcaBaseUrl = "https://gw-fatoora.zatca.gov.sa/e-invoicing/core";
}


$zatca_credentials_base_path = "zatca_credentials/";

return [
    'PRIVATE_KEY_OPTIONS' => [
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'secp256k1',
    ],
    "ZATCA_CREDENTIALS_BASE_PATH" => $zatca_credentials_base_path,
    'CONFIG_CNF_DATA' => [
        'emailAddress',
        'commonName',
        'country',
        'organizationalUnitName',
        'organizationName',
        'serialNumber',
        'vatNumber',
        'invoiceType',
        'registeredAddress',
        'businessCategory'
    ],
    "CONFIG_CNF_FILE_TEMPLATE" => $CONFIG_CNF_FILE_TEMPLATE,
    'ZATCA_MODE' => $zatcaMode,
    'ZATCA_ORIGIN' => $zatcaBaseUrl,
    'CLEARANCE_STATUS' => 0,
    'SEP' => str_starts_with(PHP_OS, 'WIN') ? "\\" : "/",
    "onLiveServer" => true
];
