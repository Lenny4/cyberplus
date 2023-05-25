<?php
/*Génération de la session*/
/*session_id("session1"); //nomination de la session
session_start(); //Démareage du système de session*/

/* Copyright (C) 2012      Mikael Carlavan        <mcarlavan@qis-network.com>
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

/**
 *        \file       htdocs/public/cmcic/payment.php
 *        \ingroup    cmcic
 *        \brief      File to offer a payment form for an invoice
 */

define("NOLOGIN", 1);        // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1);    // We accept to go on this page from external web site.

$res = @include("../main.inc.php");                    // For root directory
if (!$res) $res = @include("../../main.inc.php");    // For "custom" directory

//Spécification des chemins pour le bas des pages

require_once(DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/security.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/functions2.lib.php");
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once(DOL_DOCUMENT_ROOT . "/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT . "/commande/class/commande.class.php");

dol_include_once('/cyberplus/class/cyberplus.class.php');

// Security check
if (empty($conf->cyberplus->enabled))
	accessforbidden('', 1, 1, 1);

$langs->load("main");
$langs->load("other");
$langs->load("dict");
$langs->load("bills");
$langs->load("companies");
$langs->load("errors");
$langs->load("cyberplus@cyberplus");

$key = GETPOST("key", 'alpha');

$error = false;
$message = false;

$cyberplus = new CyberPlus($db);
$result = $cyberplus->fetch('', $key);

if ($result <= 0) {
	$error = true;
	$message = $langs->trans('NoPaymentObject');
}

// Check module configuration
if (empty($conf->global->API_KEY)) {
	$error = true;
	$message = $langs->trans('ConfigurationError');
	dol_syslog('CyberPlus: Configuration error : key is not defined');
}

if (empty($conf->global->API_SHOP_ID)) {
	$error = true;
	$message = $langs->trans('ConfigurationError');
	dol_syslog('CyberPlus: Configuration error : society ID is not defined');
}

if (!$error) {
	$isInvoice = ($cyberplus->type == 'invoice' ? true : false);

	// Get societe info
	$societyName = $mysoc->name;
	$creditorName = $societyName;
	$currency = $conf->currency;

	// Define logo and logosmall
	$urlLogo = '';
	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
		$urlLogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=1&file=logos%2' . urlencode('Fthumbs/' . $mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
		$urlLogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&entity=1&file=logos%2' . urlencode($mysoc->logo);
	}

	// Prepare form
	$language = strtoupper($langs->getDefaultLang(true));
	//$language = strtoupper($langs->getDefaultLang(0));

	//$dateTransaction = dol_print_date(dol_now(), "%d/%m/%Y:%H:%M:%S");
	$dateTransaction = gmdate("YmdHis");
	$idTransaction = sprintf("%06d", rand(0, 899999));

	$bankName = 'Banque populaire';
	$urlServer = ($conf->global->API_TEST) ? $conf->global->CYBERPLUS_URL_SERVER_TEST : $conf->global->CYBERPLUS_URL_SERVER;
	$pay_conf_period = $conf->global->PAYMENT_CONF_PERIOD ? $conf->global->PAYMENT_CONF_PERIOD : ''; //modification GIDM
	$payment_id = $conf->global->PAYMENT_ID ? $conf->global->PAYMENT_ID : 0;
	$payment_id_2 = $conf->global->PAYMENT_ID_2 ? $conf->global->PAYMENT_ID_2 : 0;//modification GIDM
	$payment_id_3 = $conf->global->PAYMENT_ID_3 ? $conf->global->PAYMENT_ID_3 : 0;//modification GIDM
	$payment_id_4 = $conf->global->PAYMENT_ID_4 ? $conf->global->PAYMENT_ID_4 : 0;//modification GIDM

	$item = ($isInvoice) ? new Facture($db) : new Commande($db);

	$result = $item->fetch($cyberplus->fk_object);


	$alreadyPaid = 0;
	$creditnotes = 0;
	$deposits = 0;
	$totalObject = 0;
	$amountTransaction = 0;
	$nPayRef = 0;
	$needPayment = false;
	$result = $item->fetch_thirdparty($item->socid);

	if ($isInvoice) {
		$alreadyPaid = $item->getSommePaiement();
		$creditnotes = $item->getSumCreditNotesUsed();
		$deposits = $item->getSumDepositsUsed();
	}

	$totalObject = $item->total_ttc;

	$alreadyPaid = empty($alreadyPaid) ? 0 : $alreadyPaid;
	$creditnotes = empty($creditnotes) ? 0 : $creditnotes;
	$deposits = empty($deposits) ? 0 : $deposits;
	$nPayRef = empty($nPayRef) ? 0 : $nPayRef;

	$totalObject = empty($totalObject) ? 0 : $totalObject;

	$amountTransaction = $totalObject - ($alreadyPaid + $creditnotes + $deposits);

	$needPayment = ($item->statut == 1) ? true : false;

	// Do nothing if payment is already completed
	if (price2num($amountTransaction, 'MT') == 0 || !$needPayment) {
		$error = true;
		$message = ($isInvoice ? $langs->trans('InvoicePaymentAlreadyDone') : $langs->trans('OrderPaymentAlreadyDone'));
		dol_syslog('CyberPlus: Payment already completed, form will not be displayed');
		$a = 0;
	}
}

if (!$error) {
	$customerEmail = utf8_encode($item->thirdparty->email);
	$customerName = utf8_encode($item->thirdparty->name);
	$customerId = utf8_encode($item->thirdparty->id);
	$customerAddress = utf8_encode($item->thirdparty->address);
	$customerZip = utf8_encode($item->thirdparty->zip);
	$customerCity = utf8_encode($item->thirdparty->town);
	$customerCountry = utf8_encode($item->thirdparty->country_code);
	$customerPhone = utf8_encode($item->thirdparty->phone);

	//payment data
	$amountTransactionNum = intval(100 * price2num($amountTransaction, 'MT')); // Cents
	$nPayRef = $item->mode_reglement_id; //appel de l'element déclencheur du mode de réglement

	//création de la variable sélectionant le mode de réglement //modification GIDM
	if ($nPayRef == $payment_id_2) {
		$count = 2;
	} elseif ($nPayRef == $payment_id_3) {
		$count = 3;
	} elseif ($nPayRef == $payment_id_4) {
		$count = 4;
	} else {
		$count = 1;
	}

	//variable de première échéance //modification GIDM
	$x = 0;
	while ($x * $count < $amountTransactionNum) {
		$x = $x + 1;
	}
	$First = $x;

	$fields = array(
		'ctx_mode' => ($conf->global->API_TEST ? 'TEST' : 'PRODUCTION'),
		'version' => 'V2',
		'language' => $language,
		'site_id' => $conf->global->API_SHOP_ID,
		'currency' => 978,
		'page_action' => 'PAYMENT',
		'action_mode' => 'INTERACTIVE',
		'payment_config' => 'SINGLE',
		'return_mode' => 'POST',
		'redirect_success_timeout' => 3,
		'redirect_error_timeout' => 3,
		'redirect_success_message' => utf8_encode(trim($langs->trans('RedirectSuccessMessage'))),
		'redirect_error_message' => utf8_encode(trim($langs->trans('RedirectErrorMessage'))),
		'amount' => $amountTransactionNum,
		'order_id' => $key,
		'cust_id' => $customerId,
		'cust_name' => $customerName,
		'cust_address' => $customerAddress,
		'cust_zip' => $customerZip,
		'cust_city' => $customerCity,
		'cust_country' => $customerCountry,
		'cust_phone' => $customerPhone,
		'cust_email' => $customerEmail,
		'trans_id' => $idTransaction,
		'trans_date' => $dateTransaction,
		'url_error' => dol_buildpath('/cyberplus/error.php', 2),
		'url_return' => dol_buildpath('/cyberplus/return.php', 2),
		'url_success' => dol_buildpath('/cyberplus/success.php', 2),
	);

	$fields2 = array(
		'ctx_mode' => ($conf->global->API_TEST ? 'TEST' : 'PRODUCTION'),
		'version' => 'V2',
		'language' => $language,
		'site_id' => $conf->global->API_SHOP_ID,
		'currency' => 978,
		'page_action' => 'PAYMENT',
		'action_mode' => 'INTERACTIVE',
		'payment_config' => 'MULTI:first=' . $First . ';count=' . $count . ';period=' . $pay_conf_period, //modification GIDM
		'return_mode' => 'POST',
		'redirect_success_timeout' => 3,
		'redirect_error_timeout' => 3,
		'redirect_success_message' => utf8_encode(trim($langs->trans('RedirectSuccessMessage'))),
		'redirect_error_message' => utf8_encode(trim($langs->trans('RedirectErrorMessage'))),
		'amount' => $amountTransactionNum,
		'order_id' => $key,
		'cust_id' => $customerId,
		'cust_name' => $customerName,
		'cust_address' => $customerAddress,
		'cust_zip' => $customerZip,
		'cust_city' => $customerCity,
		'cust_country' => $customerCountry,
		'cust_phone' => $customerPhone,
		'cust_email' => $customerEmail,
		'trans_id' => $idTransaction,
		'trans_date' => $dateTransaction,
		'url_error' => dol_buildpath('/cyberplus/error.php', 2),
		'url_return' => dol_buildpath('/cyberplus/return.php', 2),
		'url_success' => dol_buildpath('/cyberplus/success.php', 2),
	);

	ksort($fields);
	ksort($fields2);

	// Compute signature 1 & 2

	$signature = '';
	foreach ($fields as $name => $value) {
		$signature .= $value . '+';
	}
	$signature .= $conf->global->API_KEY;
	$signature = sha1($signature);

	$signature2 = '';
	foreach ($fields2 as $name2 => $value2) {
		$signature2 .= $value2 . '+';
	}
	$signature2 .= $conf->global->API_KEY;
	$signature2 = sha1($signature2);
	/*
	 * View
	 */
	$substit = array(
		'__OBJREF__' => $item->ref,
		'__SOCNAM__' => $societyName,
		'__SOCMAI__' => $conf->global->MAIN_INFO_SOCIETE_MAIL,
		'__CLINAM__' => $customerName,
		'__AMOINV__' => price2num($amountTransaction, 'MT')
	);

	if ($isInvoice) {
		$welcomeTitle = $langs->transnoentities('InvoicePaymentFormWelcomeTitle');
		$welcomeText = $langs->transnoentities('InvoicePaymentFormWelcomeText');
		$descText = $langs->transnoentities('InvoicePaymentFormDescText');
	} else {
		$welcomeTitle = $langs->transnoentities('OrderPaymentFormWelcomeTitle');
		$welcomeText = $langs->transnoentities('OrderPaymentFormWelcomeText');
		$descText = $langs->transnoentities('OrderPaymentFormDescText');
	}

	$welcomeTitle = make_substitutions($welcomeTitle, $substit);
	$welcomeText = make_substitutions($welcomeText, $substit);
	$descText = make_substitutions($descText, $substit);

	//sélection du template //modification GIDM
	if ($count == 1) {
		require_once('tpl/payment_1.tpl.php');
	} else {
		require_once('tpl/payment_2.tpl.php');
	}

} else {
	/*
	 * View
	 */
	$substit = array(
		'__SOCNAM__' => $conf->global->MAIN_INFO_SOCIETE_NOM,
		'__SOCMAI__' => $conf->global->MAIN_INFO_SOCIETE_MAIL,
	);

	$welcomeTitle = make_substitutions($langs->transnoentities('InvoicePaymentFormWelcomeTitle'), $substit);
	$message = make_substitutions($message, $substit);

	require_once('tpl/message.tpl.php');
}

//End of the page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);
llxFooter('', 'public');

//fermer la connection à la base de donnée
$db->close();
