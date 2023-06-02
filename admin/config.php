<?php
/*Génération de la session*/
/*session_id("session1"); //nomination de la session
session_start(); //Démareage du système de session*/

/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


$res = @include("../../main.inc.php");                // For root directory
if (!$res) $res = @include("../../../main.inc.php");    // For "custom" directory

require_once(DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");


$langs->load('cyberplus@cyberplus');
$langs->load('main');
$langs->load('admin');

if (!$user->admin) {
	accessforbidden();
}

//Init error
$error = false;
$message = false;

$moneticoParam = [
	'TPE_MONETICO' => [
		'value' => $conf->global->TPE_MONETICO ?: '',
		'type' => 'input',
		'description' => 'Numéro de votre TPE virtuel',
		'title' => 'Numéro TPE',
	],
	'THREE_D_SECURE_CHALLENGE_MONETICO' => [
		'value' => $conf->global->THREE_D_SECURE_CHALLENGE_MONETICO ?: 'challenge_mandated',
		'type' => 'input',
		'description' => "« no_preference » : pas de préférence (choix par défaut)<br><br>
« challenge_preferred » : challenge souhaité<br><br>
« challenge_mandated » : challenge requis<br><br>
« no_challenge_requested » : pas de challenge demandé<br><br>
« no_challenge_requested_strong_authentication » : pas de challenge demandé
– l’authentification forte du client a déjà été réalisée par le commerçant.<br><br>
« no_challenge_requested_trusted_third_party » : pas de challenge demandé –
demande d’exemption car le commerçant est un bénéficiaire de confiance du
client.<br><br>
«no_challenge_requested_risk_analysis » : pas de challenge demandé –
demande d’exemption pour un autre motif que cité précédemment (par exemple :
petit montant)",
		'title' => 'Vérification 3D secure',
	],
	'SOCIETE_MONETICO' => [
		'value' => $conf->global->SOCIETE_MONETICO ?: '',
		'type' => 'input',
		'description' => 'Code alphanumérique permettant au commerçant d’utiliser le même TPE Virtuel pour des sites différents (paramétrages distincts) se rapportant à la même activité. Il s’agit de votre code société.',
		'title' => 'Code site',
	],
];
$action = GETPOST("action");

if ($action == 'update') {
	foreach ($moneticoParam as $key => $value) {
		$moneticoParam[$key]['value'] = trim(GETPOST($key));
	}
}

$api_test = $conf->global->API_TEST ? $conf->global->API_TEST : 0;
$api_key = $conf->global->API_KEY ? $conf->global->API_KEY : '';
$api_shop_id = $conf->global->API_SHOP_ID ? $conf->global->API_SHOP_ID : '';
$security_token = $conf->global->SECURITY_TOKEN ? $conf->global->SECURITY_TOKEN : '';

$delivery_receipt_email = $conf->global->DELIVERY_RECEIPT_EMAIL ? $conf->global->DELIVERY_RECEIPT_EMAIL : 0;
$cc_email = $conf->global->CC_EMAIL ? $conf->global->CC_EMAIL : '';
$cc_emails = $conf->global->CC_EMAILS ? $conf->global->CC_EMAILS : '';
$update_invoice_statut = $conf->global->UPDATE_INVOICE_STATUT ? $conf->global->UPDATE_INVOICE_STATUT : 0;
$bank_account_id = $conf->global->BANK_ACCOUNT_ID ? $conf->global->BANK_ACCOUNT_ID : 0;
$bank_account_payment_id = $conf->global->BANK_ACCOUNT_PAYMENT_ID ? $conf->global->BANK_ACCOUNT_PAYMENT_ID : 0;

$payment_auto_send = $conf->global->PAYMENT_AUTO_SEND ? $conf->global->PAYMENT_AUTO_SEND : 0;
$payment_id = $conf->global->PAYMENT_ID ? $conf->global->PAYMENT_ID : 0;
$payment_id_2 = $conf->global->PAYMENT_ID_2 ? $conf->global->PAYMENT_ID_2 : 0;//modification GIDM
$payment_id_3 = $conf->global->PAYMENT_ID_3 ? $conf->global->PAYMENT_ID_3 : 0;//modification GIDM
$payment_id_4 = $conf->global->PAYMENT_ID_4 ? $conf->global->PAYMENT_ID_4 : 0;//modification GIDM
$payment_root_url = $conf->global->PAYMENT_ROOT_URL ? $conf->global->PAYMENT_ROOT_URL : '';
$pay_conf_period = $conf->global->PAYMENT_CONF_PERIOD ? $conf->global->PAYMENT_CONF_PERIOD : ''; //modification GIDM

// Sauvegarde parametres
if ($action == 'update') {
	$db->begin();

	$api_test = trim(GETPOST("api_test"));
	$api_key = trim(GETPOST("api_key"));
	$api_shop_id = trim(GETPOST("api_shop_id"));
	$security_token = trim(GETPOST("security_token"));
	$payment_auto_send = trim(GETPOST("payment_auto_send"));

	$delivery_receipt_email = trim(GETPOST("delivery_receipt_email"));
	$cc_email = trim(GETPOST("cc_email"));
	$cc_emails = trim(GETPOST("cc_emails"));
	$update_invoice_statut = trim(GETPOST("update_invoice_statut"));
	$bank_account_id = trim(GETPOST("bank_account_id"));
	$bank_account_payment_id = trim(GETPOST("bank_account_payment_id"));
	$payment_id = trim(GETPOST("payment_id"));
	$payment_id_2 = trim(GETPOST("payment_id_2"));//modification GIDM
	$payment_id_3 = trim(GETPOST("payment_id_3"));//modification GIDM
	$payment_id_4 = trim(GETPOST("payment_id_4"));//modification GIDM
	$payment_root_url = trim(GETPOST("payment_root_url"));
	$pay_conf_period = trim(GETPOST("payment_config_period"));//modification GIDM

	dolibarr_set_const($db, 'API_TEST', $api_test, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'API_KEY', $api_key, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'API_SHOP_ID', $api_shop_id, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'SECURITY_TOKEN', $security_token, 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, 'DELIVERY_RECEIPT_EMAIL', $delivery_receipt_email, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'CC_EMAIL', $cc_email, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'CC_EMAILS', $cc_emails, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'UPDATE_INVOICE_STATUT', $update_invoice_statut, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'PAYMENT_AUTO_SEND', $payment_auto_send, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'BANK_ACCOUNT_ID', $bank_account_id, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'BANK_ACCOUNT_PAYMENT_ID', $bank_account_payment_id, 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, 'PAYMENT_ID', $payment_id, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'PAYMENT_ID_2', $payment_id_2, 'chaine', 0, '', $conf->entity);//modification GIDM
	dolibarr_set_const($db, 'PAYMENT_ID_3', $payment_id_3, 'chaine', 0, '', $conf->entity);//modification GIDM
	dolibarr_set_const($db, 'PAYMENT_ID_4', $payment_id_4, 'chaine', 0, '', $conf->entity);    //modification GIDM
	dolibarr_set_const($db, 'PAYMENT_ROOT_URL', $payment_root_url, 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'PAYMENT_CONF_PERIOD', $pay_conf_period, 'chaine', 0, '', $conf->entity);//modification GIDM

	foreach ($moneticoParam as $key => $value) {
		$newValue = trim(GETPOST($key));
		dolibarr_set_const($db, $key, $newValue, 'chaine', 0, '', $conf->entity);
	}

	if (isset($_FILES['KEY_MONETICO'])) {
		$filePath = __DIR__ . '/../' . $_FILES['KEY_MONETICO']['name'];
		move_uploaded_file($_FILES['KEY_MONETICO']['tmp_name'], $filePath);
		dolibarr_set_const($db, 'KEY_MONETICO', $_FILES['KEY_MONETICO']['name'], 'chaine', 0, '', $conf->entity);
	}

	$db->commit();

	$message = $langs->trans("SetupSaved");
	$error = false;
}

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

$htmltooltips = array(
	'ApiTest' => $langs->trans("ApiTestTooltip"),
	'ApiKey' => $langs->trans("ApiKeyTooltip"),
	'ApiShopId' => $langs->trans("ApiShipIdTooltip"),
	'SecurityToken' => $langs->trans("SecurityTokenTooltip"),
	'DeliveryReceiptEmail' => $langs->trans("DeliveryReceiptEmailTooltip"),
	'CcEmail' => $langs->trans("CcEmailTooltip"),
	'CcEmails' => $langs->trans("CcEmailsTooltip"),
	'UpdateInvoiceStatut' => $langs->trans("UpdateInvoiceStatutTooltip"),
	'BankAccountId' => $langs->trans("BankAccountIdTooltip"),
	'BankAccountPaymentId' => $langs->trans("BankAccountPaymentIdTooltip"),
	'PaymentId' => $langs->trans("PaymentIdTooltip"),
	'PaymentId2' => $langs->trans("PaymentIdTooltip2"),
	'PaymentId3' => $langs->trans("PaymentIdTooltip3"),
	'PaymentId4' => $langs->trans("PaymentIdTooltip4"),
	'PaymentAutoSend' => $langs->trans("PaymentAutoSendTooltip"),
	'PaymentRootUrl' => $langs->trans("PaymentRootUrlTooltip"),
	'PaymentConfigPer' => $langs->trans("PaymentConfigPeriode"),
);
foreach ($moneticoParam as $key => $value) {
	$htmltooltips[$key] = $value['description'];
}

$form = new Form($db);

require_once("../tpl/admin.config.tpl.php");

$db->close();

?>
