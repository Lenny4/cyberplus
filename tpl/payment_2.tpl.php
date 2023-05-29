<?php
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
 *        \file       htdocs/public/cmcic/tpl/payment_form.php
 *        \ingroup    cmcic
 */

if (empty($conf->cyberplus->enabled))
	exit;

header('Content-type: text/html; charset=utf-8');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta name="robots" content="noindex,nofollow"/>
	<title><?php echo $langs->trans('PaymentFormTitle'); ?></title>
	<link rel="stylesheet" type="text/css"
		  href="<?php echo DOL_URL_ROOT . $conf->css . '?lang=' . $langs->defaultlang; ?>"/>
	<style type="text/css">

		body {
			width: 50%;
			margin: auto;
			text-align: center;
			z-index: 2;
		}

		#logo {
			width: 100%;
			margin: 30px 0px 30px 0px;
		}

		.payment-content {
			text-align: center;
			position: relative;
			z-index: 3; /* permet de préciser l'empilement des éléments d'une page*/
		}

		#payment-table {
			width: 100%;
			text-align: left;
			border: solid 1px rgba(168, 168, 168, .4); /* définir les propriétées liés à la bordure*/
			border-radius: 8px; /*dimensionner la forme des contours à la bordure*/
			box-shadow: 4px 4px 4px #DDD; /*Ajouter l'ombre à la bordure*/
			padding: 8px; /*définir les différences d'écart de bordure*/
		}

		#payment-table tr {
			width: 100%;
		}

		#payment-table td {
			/*border: 5px solid black;*/ /*affichage des cases du tableau*/
		}

		.liste_total {
			text-align: center;
		}

		.payment-row-left {
			width: 40%;
			text-align: left;
		}

		.payment-row-right {
			width: 60%;
			text-align: right;
		}

		#idframe {
			width: 100%;
			height: 728px;
		}

		#pop {
			position: absolute;
			z-index: 2; /* permet de préciser l'empilement des éléments d'une page*/
			width: 60%;
			top: 185px;
			left: 20%;
			bottom: auto;
			border-radius: 15px;
		}

		#img_close {
			max-width: 100%;
			max-height: 100%;
		}

		.close {
			position: absolute;
			width: 50px;
			height: auto;
			top: 2%;
			display: block;
			right: 1%;
			left: auto;
			cursor: pointer;
		}

		#btn {
			z-index: -1;
			display: none;
		}

		#text {
			text-align: left;
		}

		.cb_1, .cb_2 {
			width: auto;
			height: 80px;
			margin: auto;
		}

		.cb_1 {
			margin-left: 70%;
		}
	</style>
</head>

<body>
<div id="btn">
	<img class="close" id="img_close" src="./img/icons-fermer.png" onclick="closeForm()">
</div>
<div id="logo">
	<?php if (!empty($urlLogo)) { ?>
		<img id="paymentlogo" title="<?php echo $societyName; ?>" src="<?php echo $urlLogo; ?>" alt=""/>
	<?php } ?>
</div>

<div class="payment-content" id="myForm">
	<h1><?php echo $welcomeTitle; ?></h1><br/>
	<div id=text>
		<p><?php echo $welcomeText; ?></p>
		<p><?php echo $descText; ?></p>
	</div>

	<table id="payment-table">
		<tr class="liste_total">
			<td colspan="2"><?php echo($isInvoice ? $langs->trans('InvoicePaymentInfo') : $langs->trans('OrderPaymentInfo')); ?></td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo $langs->trans('Creditor'); ?> :</td>
			<td class="payment-row-right" colspan="2"><strong><?php echo $creditorName; ?></strong></td>
		</tr>
		<tr>
			<td class="payment-row-left">
				<?php echo($isInvoice ? $langs->trans('InvoiceReference') : $langs->trans('OrderReference')); ?> :
			</td>
			<td class="payment-row-right" colspan="2"><strong><?php echo $item->ref; ?></strong></td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo $langs->trans('TransactionReference'); ?> :</td>
			<td class="payment-row-right" colspan="2"><strong><?php echo $idTransaction; ?></strong></td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo $langs->trans('CustomerName'); ?> :</td>
			<td class="payment-row-right" colspan="2"><strong><?php echo $customerName; ?></strong></td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo $langs->trans('CustomerEmail'); ?> :</td>
			<td class="payment-row-right" colspan="2"><strong><?php echo $customerEmail; ?></strong></td>
		</tr>
		<tr class="liste_total">
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo($isInvoice ? $langs->trans('InvoiceAmount') : $langs->trans('OrderAmount')); ?>
				:
			</td>
			<td class="payment-row-right" colspan="2">
				<strong><?php echo price($totalObject); ?> <?php echo $currency; ?> TTC</strong></td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo($langs->trans('AmountAlreadyPaid')); ?> :</td>
			<td class="payment-row-right" colspan="2">
				<strong><?php echo price($alreadyPaid); ?> <?php echo $currency; ?> TTC</strong></td>
		</tr>
		<tr>
			<td class="payment-row-left"><?php echo $langs->trans('AmountToPay'); ?> :</td>
			<td class="payment-row-right" colspan="2">
				<strong><?php echo price($amountTransaction); ?> <?php echo $currency; ?> TTC</strong></td>
		<tr class="liste_total">

			<td colspan="2" class="payment-button_1" width="90%">
				<form action="<?php echo $urlServer; ?>" method="post" id="PaymentRequest" target=iframe>
					<?php foreach ($fields as $name => $value) { ?>
						<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>"/>
					<?php } ?>
					<input type="hidden" name="signature" value="<?php echo $signature; ?>"/>
					<input class="cb_1" type="image" src="img/Logo_CB_1.png" value="Payer" onclick="openForm()"/>
				</form>
			</td>
			<td colspan="2" class="payment-button_2">
				<form action="<?php echo $urlServer; ?>" method="post" id="PaymentRequest" target=iframe>
					<?php foreach ($fields2 as $name2 => $value2) { ?>
						<input type="hidden" name="<?php echo $name2; ?>" value="<?php echo $value2; ?>"/>
					<?php } ?>
					<input type="hidden" name="signature" value="<?php echo $signature2; ?>"/>
					<input class="cb_2" type="image" id="button_2" src="<?php echo 'img/Logo_CB_' . $count . '.png' ?>"
						   value="Payer" onclick="openForm()"/>
				</form>
			</td>
		</tr>
	</table>
</div>

<div id="pop">
	<iframe name="iframe" height="90%" frameborder="0" id="idframe" scrolling="yes"></iframe>
</div>

<script type="text/javascript">

	function openForm() { /* la fonction openForm est utilisé pour ouvrir le popup avec les informations et qui est actionner par le onclick*/
		document.getElementById("pop").style.boxShadow = "0px 0px 0px 9999px rgba(0, 0, 0, 0.6)"; /* Utiliser pour ajouter l'ombre d'une popup avec l'opocacité*/
		document.getElementById("pop").style.zIndex = "4";
		document.getElementById("btn").style.display = "contents"; /* Pour l'affichage du bouton fermeture*/
		document.getElementById("pop").style.display = "block";  /* Pour l'affichage du popup*/
		document.getElementById("idframe").style.borderRadius = "15px"; /* Pour dimensionner le popup*/
		document.getElementById("idframe").style.backgroundImage = "url(<?php echo $urlLogo ?>)"; /*affichage du logo pop up*/
		document.getElementById("idframe").style.backgroundRepeat = "no-repeat";
		document.getElementById("idframe").style.backgroundColor = "white";
		document.getElementById("idframe").style.backgroundPosition = "center";
	}

	function closeForm() { /* la fonction closeForm est utilisé pour fermet le popup */
		document.getElementById("pop").style.zIndex = "2";
		document.getElementById("btn").style.display = "none";  /* Pour quand l'utisateur actione le button close qu'il disparaisse en même temps que la fermeture */
		document.getElementById("pop").style.display = "none";  /* Pour que le popup disparaisse après utilisation du bouton close */
		location.reload('none'); /* Utiliser pour actualiser la page après fermeture du popup -- paramètre 'none' utilisé pour empêcher le message de dialogue d'apparaître*/

	}
</script>
</body>
</html>
