<?php
/* Copyright (C) 2025       Nicolas Zaou            <nz@anatoleconseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/install/generate-demo.php
 *	\brief      Generate realistic demo data for Dolibarr (French furniture company theme)
 *	\ingroup    install
 */

// CLI bootstrap — only when executed directly, not when included from step5.php
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
	if (!defined('DOL_DOCUMENT_ROOT')) {
		require __DIR__.'/../master.inc.php';
	}

	$ret = $user->fetch('', 'admin');
	if (!($ret > 0)) {
		print 'A user with login "admin" and all permissions must exist to use this script.'."\n";
		exit(1);
	}
	$user->loadRights();

	@set_time_limit(0);
	print "***** generate-demo.php (".DOL_VERSION.") *****\n";

	$result = generateDemoData($db, $user, $langs);
	if ($result < 0) {
		print "ERROR: Demo data generation failed.\n";
		exit(1);
	}
	print "Demo data generated successfully.\n";
	exit(0);
}


/**
 * Generate realistic demo data for a French furniture company.
 *
 * @param	DoliDB		$db		Database handler
 * @param	User		$user	User performing the actions
 * @param	Translate	$langs	Translations
 * @return	int					0 on success, <0 on error
 */
function generateDemoData($db, $user, $langs)
{
	global $conf, $mysoc;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$error = 0;
	$now = dol_now();

	// Ensure entity is set — defaults to 1 if conf was not fully initialized
	// (e.g. early install context). dolibarr_set_const() and object->entity rely on it.
	if (empty($conf->entity)) {
		$conf->entity = 1;
	}

	// Disable auto PDF generation — in install context DOL_DOCUMENT_ROOT is '..'
	// which gets mangled by dol_sanitizePathName('..' -> '_'), causing fatal errors.
	// We set model_pdf on each object so PDFs can be generated manually afterwards.
	$conf->global->MAIN_DISABLE_PDF_AUTOUPDATE = 1;

	$defaultPdfModel = array(
		'Propal' => getDolGlobalString('PROPALE_ADDON_PDF', 'cyan'),
		'Commande' => getDolGlobalString('COMMANDE_ADDON_PDF', 'eratosthene'),
		'Facture' => getDolGlobalString('FACTURE_ADDON_PDF', 'sponge'),
		'CommandeFournisseur' => getDolGlobalString('COMMANDE_SUPPLIER_ADDON_PDF', 'cornas'),
		'FactureFournisseur' => getDolGlobalString('SUPPLIER_INVOICE_ADDON_PDF', 'canelle'),
		'Expedition' => getDolGlobalString('EXPEDITION_ADDON_PDF', 'espadon'),
		'Contrat' => getDolGlobalString('CONTRACT_ADDON_PDF', 'strato'),
		'Fichinter' => getDolGlobalString('FICHEINTER_ADDON_PDF', 'soleil'),
		'SupplierProposal' => getDolGlobalString('SUPPLIER_PROPOSAL_ADDON_PDF', 'aurore'),
		'Reception' => getDolGlobalString('RECEPTION_ADDON_PDF', 'squille'),
	);


	$manageTransaction = empty($db->transaction_opened);
	if ($manageTransaction) {
		$db->begin();
	}

	// Relative dates spread over the last 12 months + future
	$dateM12 = dol_time_plus_duree($now, -12, 'm');
	$dateM11 = dol_time_plus_duree($now, -11, 'm');
	$dateM10 = dol_time_plus_duree($now, -10, 'm');
	$dateM9  = dol_time_plus_duree($now, -9, 'm');
	$dateM8  = dol_time_plus_duree($now, -8, 'm');
	$dateM7  = dol_time_plus_duree($now, -7, 'm');
	$dateM6  = dol_time_plus_duree($now, -6, 'm');
	$dateM5  = dol_time_plus_duree($now, -5, 'm');
	$dateM4  = dol_time_plus_duree($now, -4, 'm');
	$dateM3  = dol_time_plus_duree($now, -3, 'm');
	$dateM2  = dol_time_plus_duree($now, -2, 'm');
	$dateM1  = dol_time_plus_duree($now, -1, 'm');
	$dateW3  = dol_time_plus_duree($now, -21, 'd');
	$dateW2  = dol_time_plus_duree($now, -14, 'd');
	$dateW1  = dol_time_plus_duree($now, -7, 'd');
	$dateD3  = dol_time_plus_duree($now, -3, 'd');
	$dateD1  = dol_time_plus_duree($now, -1, 'd');
	$dateP1  = dol_time_plus_duree($now, 1, 'm');
	$dateP2  = dol_time_plus_duree($now, 2, 'm');
	$dateP3  = dol_time_plus_duree($now, 3, 'm');
	$dateP6  = dol_time_plus_duree($now, 6, 'm');

	// ========================================================================
	// Phase 0: Main company setup
	// ========================================================================
	_demoLog("Phase 0: Company setup");
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM", "Mobilier Durand & Fils", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ADDRESS", "14 rue du Faubourg Saint-Antoine", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_ZIP", "75012", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TOWN", "Paris", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_COUNTRY", "1:FR:France", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_STATE", "75", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_MAIL", "contact@mobilier-durand.example.com", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TEL", "01 43 00 00 00", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FAX", "01 43 00 00 01", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_WEB", "https://www.mobilier-durand.example.com", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SIREN", "123456789", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SIRET", "12345678900012", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_APE", "3109B", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_TVAINTRA", "FR12123456789", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MONNAIE", "EUR", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_CAPITAL", "150000", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FORME_JURIDIQUE", "5710", 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOTE", "Entreprise familiale fondee en 1987, specialisee dans la fabrication et l\'installation de mobilier en bois massif haut de gamme.", 'chaine', 0, '', $conf->entity);

	// Build the global $mysoc (selling company) from the constants just set.
	// In an install context master.inc.php may have created it before these
	// constants existed, leaving country_code empty — which makes get_localtax()
	// dereference a null seller during every VAT computation below. Reload conf
	// so the fresh MAIN_INFO_SOCIETE_* values are visible, then rebuild $mysoc.
	$conf->setValues($db);
	// setValues() reloaded $conf->global from DB, wiping the in-memory PDF flag
	// set above — re-apply it so closeProposal()/validate() don't try to emit a
	// PDF (DOL_DOCUMENT_ROOT='..' is mangled to '_' here and fatals).
	$conf->global->MAIN_DISABLE_PDF_AUTOUPDATE = 1;
	$mysoc = new Societe($db);
	$mysoc->setMysoc($conf);

	// ========================================================================
	// Phase 1: Internal users
	// ========================================================================
	_demoLog("Phase 1: Users");
	$user1Id = 0;
	$user2Id = 0;
	$user3Id = 0;

	$newuser = new User($db);
	$newuser->lastname = 'Dupont';
	$newuser->firstname = 'Jean';
	$newuser->login = 'jdupont';
	$newuser->admin = 0;
	$newuser->employee = 1;
	$newuser->email = 'jean.dupont@mobilier-durand.example.com';
	$newuser->job = 'Responsable commercial';
	$newuser->office_phone = '01 43 00 00 10';
	$newuser->user_mobile = '06 12 34 56 78';
	$newuser->dateemployment = $dateM12;
	$newuser->salary = 2800;
	$newuser->entity = $conf->entity;
	$result = $newuser->create($user);
	if ($result > 0) {
		$user1Id = $result;
		$newuser->setPassword($user, 'Changeme1!');
	} else {
		_demoLog("  Warning: user jdupont: ".$newuser->error);
		$tmpUser = new User($db);
		if ($tmpUser->fetch('', 'jdupont') > 0) {
			$user1Id = $tmpUser->id;
		}
	}

	$newuser2 = new User($db);
	$newuser2->lastname = 'Martin';
	$newuser2->firstname = 'Marie';
	$newuser2->login = 'mmartin';
	$newuser2->admin = 0;
	$newuser2->employee = 1;
	$newuser2->email = 'marie.martin@mobilier-durand.example.com';
	$newuser2->job = 'Technicienne d\'installation';
	$newuser2->office_phone = '01 43 00 00 11';
	$newuser2->user_mobile = '06 98 76 54 32';
	$newuser2->dateemployment = dol_time_plus_duree($dateM12, -24, 'm');
	$newuser2->salary = 2400;
	$newuser2->entity = $conf->entity;
	$result = $newuser2->create($user);
	if ($result > 0) {
		$user2Id = $result;
		$newuser2->setPassword($user, 'Changeme1!');
	} else {
		_demoLog("  Warning: user mmartin: ".$newuser2->error);
		$tmpUser2 = new User($db);
		if ($tmpUser2->fetch('', 'mmartin') > 0) {
			$user2Id = $tmpUser2->id;
		}
	}

	$newuser3 = new User($db);
	$newuser3->lastname = 'Durand';
	$newuser3->firstname = 'Philippe';
	$newuser3->login = 'pdurand';
	$newuser3->admin = 0;
	$newuser3->employee = 1;
	$newuser3->email = 'philippe.durand@mobilier-durand.example.com';
	$newuser3->job = 'Ebeniste chef d\'atelier';
	$newuser3->office_phone = '01 43 00 00 12';
	$newuser3->user_mobile = '06 55 44 33 22';
	$newuser3->dateemployment = dol_time_plus_duree($dateM12, -60, 'm');
	$newuser3->salary = 3200;
	$newuser3->entity = $conf->entity;
	$result = $newuser3->create($user);
	if ($result > 0) {
		$user3Id = $result;
		$newuser3->setPassword($user, 'Changeme1!');
	} else {
		_demoLog("  Warning: user pdurand: ".$newuser3->error);
		$tmpUser3 = new User($db);
		if ($tmpUser3->fetch('', 'pdurand') > 0) {
			$user3Id = $tmpUser3->id;
		}
	}

	// ========================================================================
	// Phase 2: Bank accounts
	// ========================================================================
	$bankAccountId = 0;
	$cashAccountId = 0;

	if (isModEnabled('bank')) {
		_demoLog("Phase 2: Bank accounts");
		require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

		$bankAccount = new Account($db);
		$bankAccount->ref = 'BNP1';
		$bankAccount->label = 'Compte courant BNP';
		$bankAccount->bank = 'BNP Paribas';
		$bankAccount->courant = Account::TYPE_CURRENT;
		$bankAccount->country_id = 1;
		$bankAccount->currency_code = 'EUR';
		$bankAccount->min_allowed = 0;
		$bankAccount->min_desired = 1000;
		$bankAccount->iban = 'FR7630004000031234567890143';
		$bankAccount->bic = 'BNPAFRPPXXX';
		$bankAccount->number = '00031234567890143';
		$bankAccount->date_solde = $dateM12;
		$result = $bankAccount->create($user);
		if ($result > 0) {
			$bankAccountId = $result;
		} else {
			_demoLog("  Warning: bank account: ".$bankAccount->error);
		}

		$cashAccount = new Account($db);
		$cashAccount->ref = 'CAISSE1';
		$cashAccount->label = 'Caisse';
		$cashAccount->courant = Account::TYPE_CASH;
		$cashAccount->country_id = 1;
		$cashAccount->currency_code = 'EUR';
		$cashAccount->date_solde = $dateM12;
		$result = $cashAccount->create($user);
		if ($result > 0) {
			$cashAccountId = $result;
		} else {
			_demoLog("  Warning: cash account: ".$cashAccount->error);
		}
	}

	// ========================================================================
	// Phase 3: Warehouses
	// ========================================================================
	$warehouseId = 0;
	$warehouseShowroomId = 0;

	if (isModEnabled('stock')) {
		_demoLog("Phase 3: Warehouses");
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

		$warehouse = new Entrepot($db);
		$warehouse->label = 'Atelier principal';
		$warehouse->lieu = 'Zone Industrielle';
		$warehouse->address = '8 rue des Artisans';
		$warehouse->zip = '75012';
		$warehouse->town = 'Paris';
		$warehouse->country_id = 1;
		$warehouse->description = 'Atelier de fabrication et stockage matieres premieres';
		$warehouse->statut = Entrepot::STATUS_OPEN_ALL;
		$result = $warehouse->create($user);
		if ($result > 0) {
			$warehouseId = $result;
		} else {
			_demoLog("  Warning: warehouse: ".$warehouse->error);
		}

		$showroom = new Entrepot($db);
		$showroom->label = 'Showroom';
		$showroom->lieu = 'Faubourg Saint-Antoine';
		$showroom->address = '14 rue du Faubourg Saint-Antoine';
		$showroom->zip = '75012';
		$showroom->town = 'Paris';
		$showroom->country_id = 1;
		$showroom->description = 'Espace d\'exposition pour les clients';
		$showroom->statut = Entrepot::STATUS_OPEN_ALL;
		$result = $showroom->create($user);
		if ($result > 0) {
			$warehouseShowroomId = $result;
		}
	}

	// ========================================================================
	// Phase 4: Third parties + contacts
	// ========================================================================
	$socClient1Id = 0;
	$socClient2Id = 0;
	$socClient3Id = 0;
	$socClient4Id = 0;
	$socClient5Id = 0;
	$socFourn1Id = 0;
	$socFourn2Id = 0;
	$socFourn3Id = 0;
	$socFourn4Id = 0;
	$contact1Id = 0;
	$contact2Id = 0;
	$contact3Id = 0;

	if (isModEnabled('societe')) {
		_demoLog("Phase 4: Third parties and contacts");
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		// Customer 1 — large hotel
		$soc = new Societe($db);
		$soc->name = 'Hotel & Spa Le Grand Bleu';
		$soc->name_alias = 'Grand Bleu';
		$soc->client = 1;
		$soc->fournisseur = 0;
		$soc->address = '25 boulevard de la Croisette';
		$soc->zip = '06400';
		$soc->town = 'Cannes';
		$soc->country_id = 1;
		$soc->country_code = 'FR';
		$soc->phone = '04 93 00 00 01';
		$soc->fax = '04 93 00 00 02';
		$soc->email = 'direction@legrandbleu.example.com';
		$soc->url = 'https://www.legrandbleu.example.com';
		$soc->tva_assuj = 1;
		$soc->tva_intra = 'FR45987654321';
		$soc->idprof1 = '987654321';
		$soc->idprof2 = '98765432100015';
		$soc->capital = 500000;
		$soc->fk_forme_juridique = 5710;
		$soc->code_client = -1;
		$soc->typent_id = 5;
		$soc->effectif_id = 3;
		$soc->note_private = 'Client principal depuis 2024. Contrat ameublement complet hotel 120 chambres. Contact privilegie: Sophie Leclerc. Reglement toujours ponctuel.';
		$soc->note_public = 'Hotel 4 etoiles, 120 chambres, renove en 2024.';
		$result = $soc->create($user);
		if ($result > 0) {
			$socClient1Id = $result;
			$contact = new Contact($db);
			$contact->socid = $socClient1Id;
			$contact->lastname = 'Leclerc';
			$contact->firstname = 'Sophie';
			$contact->poste = 'Directrice des achats';
			$contact->email = 'sophie.leclerc@legrandbleu.example.com';
			$contact->phone_pro = '04 93 00 00 01';
			$contact->phone_mobile = '06 11 22 33 44';
			$contact->note_private = 'Interlocutrice principale pour tous les projets mobilier';
			$result = $contact->create($user);
			if ($result > 0) {
				$contact1Id = $result;
			}

			$contact1b = new Contact($db);
			$contact1b->socid = $socClient1Id;
			$contact1b->lastname = 'Dupuis';
			$contact1b->firstname = 'Marc';
			$contact1b->poste = 'Responsable technique';
			$contact1b->email = 'marc.dupuis@legrandbleu.example.com';
			$contact1b->phone_pro = '04 93 00 00 03';
			$contact1b->note_private = 'A contacter pour les aspects techniques et livraisons sur site';
			$contact1b->create($user);
		}

		// Customer 2 — architect firm
		$soc2 = new Societe($db);
		$soc2->name = "Cabinet d'Architectes Moreau & Associes";
		$soc2->name_alias = 'Cabinet Moreau';
		$soc2->client = 1;
		$soc2->fournisseur = 0;
		$soc2->address = '12 place Bellecour';
		$soc2->zip = '69002';
		$soc2->town = 'Lyon';
		$soc2->country_id = 1;
		$soc2->country_code = 'FR';
		$soc2->phone = '04 72 00 00 01';
		$soc2->email = 'contact@cabinet-moreau.example.com';
		$soc2->url = 'https://www.cabinet-moreau.example.com';
		$soc2->tva_assuj = 1;
		$soc2->tva_intra = 'FR33456789012';
		$soc2->idprof1 = '456789012';
		$soc2->idprof2 = '45678901200023';
		$soc2->capital = 80000;
		$soc2->fk_forme_juridique = 5499;
		$soc2->code_client = -1;
		$soc2->typent_id = 8;
		$soc2->note_private = 'Client regulier - mobilier de bureau et espaces d\'accueil. Prescripteur important, recommande nos produits a ses clients.';
		$result = $soc2->create($user);
		if ($result > 0) {
			$socClient2Id = $result;
			$contact2 = new Contact($db);
			$contact2->socid = $socClient2Id;
			$contact2->lastname = 'Moreau';
			$contact2->firstname = 'Pierre';
			$contact2->poste = 'Architecte principal';
			$contact2->email = 'pierre.moreau@cabinet-moreau.example.com';
			$contact2->phone_pro = '04 72 00 00 01';
			$contact2->phone_mobile = '06 22 33 44 55';
			$result = $contact2->create($user);
			if ($result > 0) {
				$contact2Id = $result;
			}
		}

		// Customer 3 — prospect restaurant
		$soc3 = new Societe($db);
		$soc3->name = 'Restaurant La Belle Epoque';
		$soc3->client = 2;
		$soc3->fournisseur = 0;
		$soc3->address = '8 cours de l\'Intendance';
		$soc3->zip = '33000';
		$soc3->town = 'Bordeaux';
		$soc3->country_id = 1;
		$soc3->country_code = 'FR';
		$soc3->phone = '05 56 00 00 01';
		$soc3->email = 'contact@labelleepoque.example.com';
		$soc3->tva_assuj = 1;
		$soc3->code_client = -1;
		$soc3->typent_id = 8;
		$soc3->note_private = 'Prospect chaud - a visite le showroom en mars. Interesse par l\'ameublement complet de la nouvelle salle.';
		$result = $soc3->create($user);
		if ($result > 0) {
			$socClient3Id = $result;
			$contact3 = new Contact($db);
			$contact3->socid = $socClient3Id;
			$contact3->lastname = 'Garnier';
			$contact3->firstname = 'Camille';
			$contact3->poste = 'Gerante';
			$contact3->email = 'camille.garnier@labelleepoque.example.com';
			$contact3->phone_mobile = '06 77 88 99 00';
			$result = $contact3->create($user);
			if ($result > 0) {
				$contact3Id = $result;
			}
		}

		// Customer 4 — coworking space
		$soc4 = new Societe($db);
		$soc4->name = 'WorkSpace Republique';
		$soc4->client = 1;
		$soc4->fournisseur = 0;
		$soc4->address = '30 avenue de la Republique';
		$soc4->zip = '75011';
		$soc4->town = 'Paris';
		$soc4->country_id = 1;
		$soc4->country_code = 'FR';
		$soc4->phone = '01 55 00 00 01';
		$soc4->email = 'hello@workspace-republique.example.com';
		$soc4->url = 'https://www.workspace-republique.example.com';
		$soc4->tva_assuj = 1;
		$soc4->code_client = -1;
		$soc4->typent_id = 5;
		$soc4->note_private = 'Nouveau client - premier projet bureaux partages, 50 postes de travail.';
		$result = $soc4->create($user);
		if ($result > 0) {
			$socClient4Id = $result;
			$contact4 = new Contact($db);
			$contact4->socid = $socClient4Id;
			$contact4->lastname = 'Nguyen';
			$contact4->firstname = 'Linh';
			$contact4->poste = 'Directrice operations';
			$contact4->email = 'linh.nguyen@workspace-republique.example.com';
			$contact4->phone_mobile = '06 33 44 55 66';
			$contact4->create($user);
		}

		// Customer 5 — prospect mairie
		$soc5 = new Societe($db);
		$soc5->name = 'Mairie de Vincennes';
		$soc5->client = 2;
		$soc5->fournisseur = 0;
		$soc5->address = '53 bis rue de Fontenay';
		$soc5->zip = '94300';
		$soc5->town = 'Vincennes';
		$soc5->country_id = 1;
		$soc5->country_code = 'FR';
		$soc5->phone = '01 43 98 66 00';
		$soc5->tva_assuj = 0;
		$soc5->code_client = -1;
		$soc5->typent_id = 7;
		$soc5->note_private = 'Prospect - appel d\'offres mobilier mediatheque en preparation pour septembre.';
		$result = $soc5->create($user);
		if ($result > 0) {
			$socClient5Id = $result;
			$contact5 = new Contact($db);
			$contact5->socid = $socClient5Id;
			$contact5->lastname = 'Rousseau';
			$contact5->firstname = 'Anne';
			$contact5->poste = 'Responsable marches publics';
			$contact5->email = 'a.rousseau@vincennes.example.com';
			$contact5->phone_pro = '01 43 98 66 10';
			$contact5->create($user);
		}

		// Supplier 1 — wood
		$socf1 = new Societe($db);
		$socf1->name = 'Bois & Materiaux du Nord';
		$socf1->name_alias = 'BMN';
		$socf1->client = 0;
		$socf1->fournisseur = 1;
		$socf1->address = '45 rue de la Gare';
		$socf1->zip = '59000';
		$socf1->town = 'Lille';
		$socf1->country_id = 1;
		$socf1->country_code = 'FR';
		$socf1->phone = '03 20 00 00 01';
		$socf1->email = 'commandes@boisnord.example.com';
		$socf1->tva_assuj = 1;
		$socf1->tva_intra = 'FR67111222333';
		$socf1->code_fournisseur = -1;
		$socf1->note_private = 'Fournisseur principal bois massif. Delai livraison 2-3 semaines. Prix negocies annuellement.';
		$result = $socf1->create($user);
		if ($result > 0) {
			$socFourn1Id = $result;
			$contactf1 = new Contact($db);
			$contactf1->socid = $socFourn1Id;
			$contactf1->lastname = 'Vandenberghe';
			$contactf1->firstname = 'Thomas';
			$contactf1->poste = 'Responsable commercial';
			$contactf1->email = 'thomas.v@boisnord.example.com';
			$contactf1->phone_pro = '03 20 00 00 01';
			$contactf1->phone_mobile = '06 44 55 66 77';
			$contactf1->create($user);
		}

		// Supplier 2 — fabrics
		$socf2 = new Societe($db);
		$socf2->name = 'Tissus & Textiles Beaumont';
		$socf2->client = 0;
		$socf2->fournisseur = 1;
		$socf2->address = '3 rue de la Soie';
		$socf2->zip = '69001';
		$socf2->town = 'Lyon';
		$socf2->country_id = 1;
		$socf2->country_code = 'FR';
		$socf2->phone = '04 78 00 00 01';
		$socf2->email = 'ventes@tissus-beaumont.example.com';
		$socf2->tva_assuj = 1;
		$socf2->code_fournisseur = -1;
		$socf2->note_private = 'Specialiste tissus ameublement haut de gamme. Large gamme de coloris.';
		$result = $socf2->create($user);
		if ($result > 0) {
			$socFourn2Id = $result;
			$contactf2 = new Contact($db);
			$contactf2->socid = $socFourn2Id;
			$contactf2->lastname = 'Beaumont';
			$contactf2->firstname = 'Claire';
			$contactf2->poste = 'Directrice';
			$contactf2->email = 'claire@tissus-beaumont.example.com';
			$contactf2->phone_mobile = '06 55 66 77 88';
			$contactf2->create($user);
		}

		// Supplier 3 — hardware (also customer)
		$socf3 = new Societe($db);
		$socf3->name = 'Quincaillerie Generale Martin';
		$socf3->client = 1;
		$socf3->fournisseur = 1;
		$socf3->address = '22 avenue de la Republique';
		$socf3->zip = '75011';
		$socf3->town = 'Paris';
		$socf3->country_id = 1;
		$socf3->country_code = 'FR';
		$socf3->phone = '01 48 00 00 01';
		$socf3->email = 'contact@quincaillerie-martin.example.com';
		$socf3->tva_assuj = 1;
		$socf3->code_client = -1;
		$socf3->code_fournisseur = -1;
		$socf3->note_private = 'Fournisseur et client. Livre visserie et quincaillerie. Achete des meubles pour son magasin.';
		$result = $socf3->create($user);
		if ($result > 0) {
			$socFourn3Id = $result;
			$contactf3 = new Contact($db);
			$contactf3->socid = $socFourn3Id;
			$contactf3->lastname = 'Martin';
			$contactf3->firstname = 'Luc';
			$contactf3->poste = 'Gerant';
			$contactf3->email = 'luc.martin@quincaillerie-martin.example.com';
			$contactf3->phone_pro = '01 48 00 00 01';
			$contactf3->create($user);
		}

		// Supplier 4 — varnish and finishes
		$socf4 = new Societe($db);
		$socf4->name = 'Vernis Pro France';
		$socf4->client = 0;
		$socf4->fournisseur = 1;
		$socf4->address = '18 zone artisanale Les Platanes';
		$socf4->zip = '91000';
		$socf4->town = 'Evry';
		$socf4->country_id = 1;
		$socf4->country_code = 'FR';
		$socf4->phone = '01 60 00 00 01';
		$socf4->email = 'commandes@vernispro.example.com';
		$socf4->tva_assuj = 1;
		$socf4->code_fournisseur = -1;
		$socf4->note_private = 'Fournisseur vernis et finitions ecologiques. Certifie EU Ecolabel.';
		$result = $socf4->create($user);
		if ($result > 0) {
			$socFourn4Id = $result;
			$contactf4 = new Contact($db);
			$contactf4->socid = $socFourn4Id;
			$contactf4->lastname = 'Petit';
			$contactf4->firstname = 'Nicolas';
			$contactf4->poste = 'Technico-commercial';
			$contactf4->email = 'n.petit@vernispro.example.com';
			$contactf4->phone_mobile = '06 88 99 00 11';
			$contactf4->create($user);
		}
	}

	// ========================================================================
	// Phase 5: Products and services
	// ========================================================================
	$prodTableId = 0;
	$prodChaiseId = 0;
	$prodEtagereId = 0;
	$prodBureauId = 0;
	$prodBancId = 0;
	$servInstallId = 0;
	$servConseilId = 0;
	$servEntretienId = 0;
	$servLivraisonId = 0;
	$matCheneId = 0;
	$matNoyerId = 0;
	$matQuincailleId = 0;

	if (isModEnabled('product') || isModEnabled('service')) {
		_demoLog("Phase 5: Products and services");
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		// Product 1 — Oak table
		$prod1 = new Product($db);
		$prod1->type = 0;
		$prod1->ref = 'MEUB-TABLE-CH';
		$prod1->label = 'Table en chene massif 180cm';
		$prod1->description = 'Table rectangulaire en chene massif, finition vernis mat. Dimensions : 180 x 90 x 75 cm. Plateau epaisseur 4 cm. Pieds tournes. Fabrication artisanale francaise.';
		$prod1->note = 'Delai fabrication : 4 semaines. Possibilite de personnalisation (taille, finition).';
		$prod1->status = 1;
		$prod1->status_buy = 1;
		$prod1->weight = 45;
		$prod1->weight_units = 0;
		$prod1->length = 180;
		$prod1->length_units = -2;
		$prod1->width = 90;
		$prod1->width_units = -2;
		$prod1->height = 75;
		$prod1->height_units = -2;
		$prod1->country_id = 1;
		$prod1->tva_tx = '20.0';
		$prod1->price_base_type = 'HT';
		$prod1->price = 850;
		$result = $prod1->create($user);
		if ($result > 0) {
			$prodTableId = $result;
			$prod1->updatePrice($prod1->price, $prod1->price_base_type, $user, $prod1->tva_tx);
		}

		// Product 2 — Upholstered chair
		$prod2 = new Product($db);
		$prod2->type = 0;
		$prod2->ref = 'MEUB-CHAISE-TIS';
		$prod2->label = 'Chaise rembourrée tissu';
		$prod2->description = 'Chaise avec assise rembourrée en tissu, structure hetre massif. Coloris au choix : bleu marine, gris anthracite, bordeaux. Garantie 5 ans.';
		$prod2->status = 1;
		$prod2->status_buy = 1;
		$prod2->weight = 8;
		$prod2->weight_units = 0;
		$prod2->country_id = 1;
		$prod2->tva_tx = '20.0';
		$prod2->price_base_type = 'HT';
		$prod2->price = 185;
		$result = $prod2->create($user);
		if ($result > 0) {
			$prodChaiseId = $result;
			$prod2->updatePrice($prod2->price, $prod2->price_base_type, $user, $prod2->tva_tx);
		}

		// Product 3 — Wall shelf
		$prod3 = new Product($db);
		$prod3->type = 0;
		$prod3->ref = 'MEUB-ETAG-NYER';
		$prod3->label = 'Etagere murale noyer 120cm';
		$prod3->description = 'Etagere murale en noyer massif avec fixations invisibles. Longueur 120 cm, profondeur 25 cm. Charge max 30 kg.';
		$prod3->status = 1;
		$prod3->status_buy = 1;
		$prod3->weight = 12;
		$prod3->weight_units = 0;
		$prod3->country_id = 1;
		$prod3->tva_tx = '20.0';
		$prod3->price_base_type = 'HT';
		$prod3->price = 320;
		$result = $prod3->create($user);
		if ($result > 0) {
			$prodEtagereId = $result;
			$prod3->updatePrice($prod3->price, $prod3->price_base_type, $user, $prod3->tva_tx);
		}

		// Product 4 — Standing desk
		$prod4 = new Product($db);
		$prod4->type = 0;
		$prod4->ref = 'MEUB-BUREAU-REG';
		$prod4->label = 'Bureau reglable chene/acier';
		$prod4->description = 'Bureau a hauteur reglable, plateau chene massif 140x70cm sur pietement acier noir. Reglage manuel par manivelle. Hauteur 72-120 cm.';
		$prod4->status = 1;
		$prod4->status_buy = 1;
		$prod4->weight = 35;
		$prod4->weight_units = 0;
		$prod4->country_id = 1;
		$prod4->tva_tx = '20.0';
		$prod4->price_base_type = 'HT';
		$prod4->price = 680;
		$result = $prod4->create($user);
		if ($result > 0) {
			$prodBureauId = $result;
			$prod4->updatePrice($prod4->price, $prod4->price_base_type, $user, $prod4->tva_tx);
		}

		// Product 5 — Bench
		$prod5 = new Product($db);
		$prod5->type = 0;
		$prod5->ref = 'MEUB-BANC-CH';
		$prod5->label = 'Banc chene massif 160cm';
		$prod5->description = 'Banc en chene massif, finition huilee. Longueur 160 cm. Ideal pour tables a manger ou entrees.';
		$prod5->status = 1;
		$prod5->status_buy = 1;
		$prod5->weight = 22;
		$prod5->weight_units = 0;
		$prod5->country_id = 1;
		$prod5->tva_tx = '20.0';
		$prod5->price_base_type = 'HT';
		$prod5->price = 420;
		$result = $prod5->create($user);
		if ($result > 0) {
			$prodBancId = $result;
			$prod5->updatePrice($prod5->price, $prod5->price_base_type, $user, $prod5->tva_tx);
		}

		// Raw material 1 — Oak boards
		$mat1 = new Product($db);
		$mat1->type = 0;
		$mat1->ref = 'MAT-CHENE';
		$mat1->label = 'Planches chene brut';
		$mat1->description = 'Planches de chene brut 200x30x3 cm, sechage naturel, qualite ebenisterie.';
		$mat1->status = 0;
		$mat1->status_buy = 1;
		$mat1->country_id = 1;
		$mat1->tva_tx = '20.0';
		$mat1->price_base_type = 'HT';
		$mat1->price = 45;
		$result = $mat1->create($user);
		if ($result > 0) {
			$matCheneId = $result;
			$mat1->updatePrice($mat1->price, $mat1->price_base_type, $user, $mat1->tva_tx);
		}

		// Raw material 2 — Walnut boards
		$mat1b = new Product($db);
		$mat1b->type = 0;
		$mat1b->ref = 'MAT-NOYER';
		$mat1b->label = 'Planches noyer brut';
		$mat1b->description = 'Planches de noyer brut 150x25x3 cm, sechage controle.';
		$mat1b->status = 0;
		$mat1b->status_buy = 1;
		$mat1b->country_id = 1;
		$mat1b->tva_tx = '20.0';
		$mat1b->price_base_type = 'HT';
		$mat1b->price = 55;
		$result = $mat1b->create($user);
		if ($result > 0) {
			$matNoyerId = $result;
			$mat1b->updatePrice($mat1b->price, $mat1b->price_base_type, $user, $mat1b->tva_tx);
		}

		// Raw material 3 — Hardware kit
		$mat2 = new Product($db);
		$mat2->type = 0;
		$mat2->ref = 'MAT-QUINCAILLE';
		$mat2->label = 'Kit quincaillerie (vis, tourillons)';
		$mat2->description = 'Kit complet visserie et tourillons pour assemblage meuble. Contenu : 40 vis, 20 tourillons, colle.';
		$mat2->status = 0;
		$mat2->status_buy = 1;
		$mat2->country_id = 1;
		$mat2->tva_tx = '20.0';
		$mat2->price_base_type = 'HT';
		$mat2->price = 12;
		$result = $mat2->create($user);
		if ($result > 0) {
			$matQuincailleId = $result;
			$mat2->updatePrice($mat2->price, $mat2->price_base_type, $user, $mat2->tva_tx);
		}

		// Services
		if (isModEnabled('service')) {
			// Service 1 — Installation
			$serv1 = new Product($db);
			$serv1->type = 1;
			$serv1->ref = 'SERV-INSTALL';
			$serv1->label = 'Installation et montage sur site';
			$serv1->description = 'Installation sur site par notre equipe qualifiee. Tarif a la demi-journee. Inclut montage, verification et nettoyage.';
			$serv1->status = 1;
			$serv1->status_buy = 0;
			$serv1->country_id = 1;
			$serv1->duration_value = 4;
			$serv1->duration_unit = 'h';
			$serv1->tva_tx = '20.0';
			$serv1->price_base_type = 'HT';
			$serv1->price = 350;
			$result = $serv1->create($user);
			if ($result > 0) {
				$servInstallId = $result;
				$serv1->updatePrice($serv1->price, $serv1->price_base_type, $user, $serv1->tva_tx);
			}

			// Service 2 — Design consulting
			$serv2 = new Product($db);
			$serv2->type = 1;
			$serv2->ref = 'SERV-CONSEIL';
			$serv2->label = 'Conseil en amenagement interieur';
			$serv2->description = 'Conseil et accompagnement pour vos projets d\'amenagement. Prise de mesures, proposition d\'agencement, choix des materiaux et coloris.';
			$serv2->status = 1;
			$serv2->status_buy = 0;
			$serv2->country_id = 1;
			$serv2->duration_value = 1;
			$serv2->duration_unit = 'h';
			$serv2->tva_tx = '20.0';
			$serv2->price_base_type = 'HT';
			$serv2->price = 120;
			$result = $serv2->create($user);
			if ($result > 0) {
				$servConseilId = $result;
				$serv2->updatePrice($serv2->price, $serv2->price_base_type, $user, $serv2->tva_tx);
			}

			// Service 3 — Maintenance
			$serv3 = new Product($db);
			$serv3->type = 1;
			$serv3->ref = 'SERV-ENTRETIEN';
			$serv3->label = 'Entretien et renovation mobilier';
			$serv3->description = 'Entretien, reparation et renovation de vos meubles en bois massif. Poncage, revernissage, remplacement pieces.';
			$serv3->status = 1;
			$serv3->status_buy = 0;
			$serv3->country_id = 1;
			$serv3->duration_value = 1;
			$serv3->duration_unit = 'h';
			$serv3->tva_tx = '20.0';
			$serv3->price_base_type = 'HT';
			$serv3->price = 80;
			$result = $serv3->create($user);
			if ($result > 0) {
				$servEntretienId = $result;
				$serv3->updatePrice($serv3->price, $serv3->price_base_type, $user, $serv3->tva_tx);
			}

			// Service 4 — Delivery
			$serv4 = new Product($db);
			$serv4->type = 1;
			$serv4->ref = 'SERV-LIVRAISON';
			$serv4->label = 'Livraison et mise en place';
			$serv4->description = 'Transport et livraison sur site. Mise en place dans les locaux, deballage et evacuation des emballages.';
			$serv4->status = 1;
			$serv4->status_buy = 0;
			$serv4->country_id = 1;
			$serv4->tva_tx = '20.0';
			$serv4->price_base_type = 'HT';
			$serv4->price = 150;
			$result = $serv4->create($user);
			if ($result > 0) {
				$servLivraisonId = $result;
				$serv4->updatePrice($serv4->price, $serv4->price_base_type, $user, $serv4->tva_tx);
			}
		}
	}

	// ========================================================================
	// Phase 6: Initial stock movements
	// ========================================================================
	if (isModEnabled('stock') && $warehouseId > 0) {
		_demoLog("Phase 6: Stock movements");
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		$productsForStock = array(
			$prodTableId => 10, $prodChaiseId => 40, $prodEtagereId => 15,
			$prodBureauId => 8, $prodBancId => 12,
			$matCheneId => 80, $matNoyerId => 40, $matQuincailleId => 30
		);
		foreach ($productsForStock as $pid => $qty) {
			if ($pid > 0) {
				$mvt = new MouvementStock($db);
				$mvt->reception($user, $pid, $warehouseId, $qty, 0, 'Stock initial (demo)');
			}
		}

		// Some stock in showroom
		if ($warehouseShowroomId > 0) {
			$showroomStock = array($prodTableId => 2, $prodChaiseId => 6, $prodEtagereId => 3, $prodBureauId => 2, $prodBancId => 2);
			foreach ($showroomStock as $pid => $qty) {
				if ($pid > 0) {
					$mvt = new MouvementStock($db);
					$mvt->reception($user, $pid, $warehouseShowroomId, $qty, 0, 'Stock showroom (demo)');
				}
			}
		}
	}

	// ========================================================================
	// Phase 7: Categories
	// ========================================================================
	if (isModEnabled('category')) {
		_demoLog("Phase 7: Categories");
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$catMob = new Categorie($db);
		$catMob->label = 'Mobilier';
		$catMob->description = 'Meubles en bois massif';
		$catMob->type = Categorie::TYPE_PRODUCT;
		$catMob->create($user);
		if ($catMob->id > 0) {
			foreach (array($prodTableId, $prodChaiseId, $prodEtagereId, $prodBureauId, $prodBancId) as $pid) {
				if ($pid > 0) {
					$p = new Product($db);
					$p->fetch($pid);
					$catMob->add_type($p, 'product');
				}
			}
		}

		$catServ = new Categorie($db);
		$catServ->label = 'Services';
		$catServ->description = 'Prestations de services';
		$catServ->type = Categorie::TYPE_PRODUCT;
		$catServ->create($user);
		if ($catServ->id > 0) {
			foreach (array($servInstallId, $servConseilId, $servEntretienId, $servLivraisonId) as $sid) {
				if ($sid > 0) {
					$s = new Product($db);
					$s->fetch($sid);
					$catServ->add_type($s, 'product');
				}
			}
		}

		$catMatPrem = new Categorie($db);
		$catMatPrem->label = 'Matieres premieres';
		$catMatPrem->description = 'Bois brut et quincaillerie';
		$catMatPrem->type = Categorie::TYPE_PRODUCT;
		$catMatPrem->create($user);
		if ($catMatPrem->id > 0) {
			foreach (array($matCheneId, $matNoyerId, $matQuincailleId) as $mid) {
				if ($mid > 0) {
					$m = new Product($db);
					$m->fetch($mid);
					$catMatPrem->add_type($m, 'product');
				}
			}
		}

		if ($socClient1Id > 0) {
			$catPrem = new Categorie($db);
			$catPrem->label = 'Clients premium';
			$catPrem->description = 'Clients grands comptes avec conditions preferentielles';
			$catPrem->type = Categorie::TYPE_CUSTOMER;
			$catPrem->create($user);
			if ($catPrem->id > 0) {
				foreach (array($socClient1Id, $socClient4Id) as $sid) {
					if ($sid > 0) {
						$sc = new Societe($db);
						$sc->fetch($sid);
						$catPrem->add_type($sc, 'customer');
					}
				}
			}
		}

		$catProspects = new Categorie($db);
		$catProspects->label = 'Prospects chauds';
		$catProspects->description = 'Prospects avec projet identifie';
		$catProspects->type = Categorie::TYPE_CUSTOMER;
		$catProspects->create($user);
		if ($catProspects->id > 0) {
			foreach (array($socClient3Id, $socClient5Id) as $sid) {
				if ($sid > 0) {
					$sc = new Societe($db);
					$sc->fetch($sid);
					$catProspects->add_type($sc, 'customer');
				}
			}
		}

		if ($socFourn1Id > 0) {
			$catFournBois = new Categorie($db);
			$catFournBois->label = 'Fournisseurs bois';
			$catFournBois->type = Categorie::TYPE_SUPPLIER;
			$catFournBois->create($user);
			if ($catFournBois->id > 0) {
				$sf = new Societe($db);
				$sf->fetch($socFourn1Id);
				$catFournBois->add_type($sf, 'supplier');
			}
		}
	}

	// ========================================================================
	// Phase 8: Projects
	// ========================================================================
	$project1Id = 0;
	$project2Id = 0;
	$project3Id = 0;

	if (isModEnabled('project')) {
		_demoLog("Phase 8: Projects");
		require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

		// Project 1 — hotel (well advanced)
		$proj1 = new Project($db);
		$proj1->ref = 'PROJ-HOTEL';
		$proj1->title = 'Amenagement Hotel Le Grand Bleu';
		$proj1->socid = $socClient1Id;
		$proj1->date_start = $dateM10;
		$proj1->date_end = $dateM1;
		$proj1->description = 'Fourniture et installation du mobilier pour les 120 chambres et espaces communs (hall, restaurant, bar). Budget previsionnel 85 000 EUR HT.';
		$proj1->note_private = 'Projet strategique - reference pour futurs hotels. Marge visee 35%.';
		$result = $proj1->create($user);
		if ($result > 0) {
			$project1Id = $proj1->id;
			$proj1->setValid($user);

			$task1 = new Task($db);
			$task1->fk_project = $project1Id;
			$task1->ref = 'T01';
			$task1->label = 'Fabrication mobilier chambres';
			$task1->description = 'Fabrication des 120 tables de nuit et 120 bureaux';
			$task1->date_start = $dateM9;
			$task1->date_end = $dateM6;
			$task1->planned_workload = 3600 * 200;
			$task1->progress = 100;
			$task1->create($user);

			$task2 = new Task($db);
			$task2->fk_project = $project1Id;
			$task2->ref = 'T02';
			$task2->label = 'Livraison et installation etage 1-3';
			$task2->description = 'Livraison, montage et installation dans les chambres etages 1 a 3';
			$task2->date_start = $dateM5;
			$task2->date_end = $dateM3;
			$task2->planned_workload = 3600 * 80;
			$task2->progress = 100;
			$task2->create($user);

			$task3 = new Task($db);
			$task3->fk_project = $project1Id;
			$task3->ref = 'T03';
			$task3->label = 'Installation espaces communs';
			$task3->description = 'Mobilier hall, restaurant et bar';
			$task3->date_start = $dateM4;
			$task3->date_end = $dateM1;
			$task3->planned_workload = 3600 * 60;
			$task3->progress = 80;
			$task3->create($user);
		}

		// Project 2 — architect (in progress)
		$proj2 = new Project($db);
		$proj2->ref = 'PROJ-ARCHI';
		$proj2->title = 'Renovation Cabinet Moreau';
		$proj2->socid = $socClient2Id;
		$proj2->date_start = $dateM3;
		$proj2->date_end = $dateP3;
		$proj2->description = 'Fourniture de mobilier pour la renovation des bureaux du cabinet : 8 bureaux, 16 chaises, etageres, salle de reunion.';
		$proj2->note_private = 'Budget serre, le client compare avec IKEA Pro. Mettre en avant la qualite et la garantie.';
		$result = $proj2->create($user);
		if ($result > 0) {
			$project2Id = $proj2->id;
			$proj2->setValid($user);

			$task2a = new Task($db);
			$task2a->fk_project = $project2Id;
			$task2a->ref = 'T01';
			$task2a->label = 'Prise de mesures et conseil';
			$task2a->date_start = $dateM3;
			$task2a->date_end = $dateM2;
			$task2a->progress = 100;
			$task2a->create($user);

			$task2b = new Task($db);
			$task2b->fk_project = $project2Id;
			$task2b->ref = 'T02';
			$task2b->label = 'Fabrication et livraison';
			$task2b->date_start = $dateM1;
			$task2b->date_end = $dateP2;
			$task2b->progress = 30;
			$task2b->create($user);
		}

		// Project 3 — coworking (new)
		$proj3 = new Project($db);
		$proj3->ref = 'PROJ-COWORK';
		$proj3->title = 'Amenagement WorkSpace Republique';
		$proj3->socid = $socClient4Id;
		$proj3->date_start = $dateW2;
		$proj3->date_end = $dateP6;
		$proj3->description = 'Amenagement complet espace coworking 50 postes : bureaux reglables, chaises, rangements, salle reunion.';
		$result = $proj3->create($user);
		if ($result > 0) {
			$project3Id = $proj3->id;
			$proj3->setValid($user);
		}
	}

	// ========================================================================
	// Phase 9: Proposals
	// ========================================================================
	$propal1Id = 0;
	$propal2Id = 0;

	if (isModEnabled('propal') && $socClient1Id > 0) {
		_demoLog("Phase 9: Proposals");
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

		// Proposal 1 — signed (hotel, big order)
		$prop1 = new Propal($db);
		$prop1->socid = $socClient1Id;
		$prop1->date = $dateM11;
		$prop1->cond_reglement_id = 1;
		$prop1->mode_reglement_id = 2;
		$prop1->fk_project = $project1Id;
		$prop1->note_private = 'Devis negocie avec Sophie Leclerc. Remise 5% accordee sur volume.';
		$prop1->note_public = 'Offre valable 30 jours. Livraison sous 6-8 semaines apres commande.';
		$prop1->model_pdf = $defaultPdfModel['Propal'];
		$result = $prop1->create($user);
		if ($result > 0) {
			$propal1Id = $prop1->id;
			if ($prodTableId > 0) {
				$prop1->addline('Table en chene massif 180cm', 850, 6, '20.0', 0, 0, $prodTableId);
			}
			if ($prodChaiseId > 0) {
				$prop1->addline('Chaise rembourrée tissu bleu marine', 185, 24, '20.0', 0, 0, $prodChaiseId);
			}
			if ($prodBancId > 0) {
				$prop1->addline('Banc chene massif 160cm (hall)', 420, 4, '20.0', 0, 0, $prodBancId);
			}
			if ($servInstallId > 0) {
				$prop1->addline('Installation et montage sur site (4 demi-journees)', 350, 4, '20.0', 0, 0, $servInstallId);
			}
			if ($servLivraisonId > 0) {
				$prop1->addline('Livraison Cannes', 150, 2, '20.0', 0, 0, $servLivraisonId);
			}
			$prop1->valid($user);
			$prop1->closeProposal($user, 2); // STATUS_SIGNED
			if ($contact1Id > 0) {
				$prop1->add_contact($contact1Id, 'BILLING', 'external');
			}
		}

		// Proposal 2 — validated, pending (architect)
		if ($socClient2Id > 0) {
			$prop2 = new Propal($db);
			$prop2->socid = $socClient2Id;
			$prop2->date = $dateM2;
			$prop2->cond_reglement_id = 1;
			$prop2->mode_reglement_id = 2;
			$prop2->fk_project = $project2Id;
			$prop2->note_private = 'Relance prevue semaine prochaine si pas de retour.';
			$prop2->model_pdf = $defaultPdfModel['Propal'];
			$result = $prop2->create($user);
			if ($result > 0) {
				$propal2Id = $prop2->id;
				if ($prodEtagereId > 0) {
					$prop2->addline('Etagere murale noyer 120cm', 320, 4, '20.0', 0, 0, $prodEtagereId);
				}
				if ($prodBureauId > 0) {
					$prop2->addline('Bureau reglable chene/acier', 680, 8, '20.0', 0, 0, $prodBureauId);
				}
				if ($prodChaiseId > 0) {
					$prop2->addline('Chaise rembourrée tissu gris anthracite', 185, 16, '20.0', 0, 0, $prodChaiseId);
				}
				if ($servConseilId > 0) {
					$prop2->addline('Conseil en amenagement (4h)', 120, 4, '20.0', 0, 0, $servConseilId);
				}
				$prop2->valid($user);
				if ($contact2Id > 0) {
					$prop2->add_contact($contact2Id, 'BILLING', 'external');
				}
			}
		}

		// Proposal 3 — draft (prospect restaurant)
		if ($socClient3Id > 0) {
			$prop3 = new Propal($db);
			$prop3->socid = $socClient3Id;
			$prop3->date = $dateW1;
			$prop3->cond_reglement_id = 1;
			$prop3->mode_reglement_id = 2;
			$prop3->note_private = 'Brouillon en attente visite du restaurant pour prendre les mesures.';
			$prop3->model_pdf = $defaultPdfModel['Propal'];
			$result = $prop3->create($user);
			if ($result > 0) {
				if ($prodTableId > 0) {
					$prop3->addline('Table en chene massif 180cm', 850, 8, '20.0', 0, 0, $prodTableId);
				}
				if ($prodChaiseId > 0) {
					$prop3->addline('Chaise rembourrée tissu bordeaux', 185, 32, '20.0', 0, 0, $prodChaiseId);
				}
				if ($prodBancId > 0) {
					$prop3->addline('Banc chene massif 160cm', 420, 4, '20.0', 0, 0, $prodBancId);
				}
				if ($servInstallId > 0) {
					$prop3->addline('Installation (2 demi-journees)', 350, 2, '20.0', 0, 0, $servInstallId);
				}
				if ($servLivraisonId > 0) {
					$prop3->addline('Livraison Bordeaux', 150, 1, '20.0', 0, 0, $servLivraisonId);
				}
			}
		}

		// Proposal 4 — signed (coworking)
		if ($socClient4Id > 0) {
			$prop4 = new Propal($db);
			$prop4->socid = $socClient4Id;
			$prop4->date = $dateW3;
			$prop4->cond_reglement_id = 1;
			$prop4->mode_reglement_id = 2;
			$prop4->fk_project = $project3Id;
			$prop4->note_public = 'Phase 1 : 25 postes de travail. Phase 2 a planifier.';
			$prop4->model_pdf = $defaultPdfModel['Propal'];
			$result = $prop4->create($user);
			if ($result > 0) {
				if ($prodBureauId > 0) {
					$prop4->addline('Bureau reglable chene/acier', 680, 25, '20.0', 0, 0, $prodBureauId);
				}
				if ($prodChaiseId > 0) {
					$prop4->addline('Chaise rembourrée tissu gris anthracite', 185, 25, '20.0', 0, 0, $prodChaiseId);
				}
				if ($prodEtagereId > 0) {
					$prop4->addline('Etagere murale noyer 120cm', 320, 10, '20.0', 0, 0, $prodEtagereId);
				}
				if ($servInstallId > 0) {
					$prop4->addline('Installation (5 demi-journees)', 350, 5, '20.0', 0, 0, $servInstallId);
				}
				if ($servLivraisonId > 0) {
					$prop4->addline('Livraison Paris', 150, 1, '20.0', 0, 0, $servLivraisonId);
				}
				$prop4->valid($user);
				$prop4->closeProposal($user, 2); // STATUS_SIGNED
			}
		}

		// Proposal 5 — refused (prospect mairie)
		if ($socClient5Id > 0) {
			$prop5 = new Propal($db);
			$prop5->socid = $socClient5Id;
			$prop5->date = $dateM3;
			$prop5->cond_reglement_id = 1;
			$prop5->mode_reglement_id = 2;
			$prop5->note_private = 'Devis refuse - budget collectivite insuffisant. A reproposer en septembre.';
			$prop5->model_pdf = $defaultPdfModel['Propal'];
			$result = $prop5->create($user);
			if ($result > 0) {
				if ($prodTableId > 0) {
					$prop5->addline('Table en chene massif 180cm', 850, 10, '20.0', 0, 0, $prodTableId);
				}
				if ($prodChaiseId > 0) {
					$prop5->addline('Chaise rembourrée tissu bleu', 185, 40, '20.0', 0, 0, $prodChaiseId);
				}
				$prop5->valid($user);
				$prop5->closeProposal($user, 3); // STATUS_REFUSED
			}
		}
	}

	// ========================================================================
	// Phase 10: Customer orders
	// ========================================================================
	$order1Id = 0;
	$order2Id = 0;
	$order3Id = 0;

	if (isModEnabled('order') && $socClient1Id > 0) {
		_demoLog("Phase 10: Customer orders");
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

		// Order 1 — hotel (from signed proposal)
		$ord1 = new Commande($db);
		$ord1->socid = $socClient1Id;
		$ord1->date_commande = $dateM9;
		$ord1->date = $dateM9;
		$ord1->fk_project = $project1Id;
		$ord1->cond_reglement_id = 1;
		$ord1->mode_reglement_id = 2;
		$ord1->note_private = 'Commande suite devis signe. Livraison en 2 fois.';
		$ord1->model_pdf = $defaultPdfModel['Commande'];
		$result = $ord1->create($user);
		if ($result > 0) {
			$order1Id = $ord1->id;
			if ($prodTableId > 0) {
				$ord1->addline('Table en chene massif 180cm', 850, 6, '20.0', 0, 0, $prodTableId);
			}
			if ($prodChaiseId > 0) {
				$ord1->addline('Chaise rembourrée tissu bleu marine', 185, 24, '20.0', 0, 0, $prodChaiseId);
			}
			if ($prodBancId > 0) {
				$ord1->addline('Banc chene massif 160cm', 420, 4, '20.0', 0, 0, $prodBancId);
			}
			if ($servInstallId > 0) {
				$ord1->addline('Installation (4 demi-journees)', 350, 4, '20.0', 0, 0, $servInstallId);
			}
			$ord1->valid($user, $warehouseId);
			if ($contact1Id > 0) {
				$ord1->add_contact($contact1Id, 'BILLING', 'external');
			}
		}

		// Order 2 — architect
		if ($socClient2Id > 0) {
			$ord2 = new Commande($db);
			$ord2->socid = $socClient2Id;
			$ord2->date_commande = $dateM1;
			$ord2->date = $dateM1;
			$ord2->fk_project = $project2Id;
			$ord2->cond_reglement_id = 1;
			$ord2->mode_reglement_id = 2;
			$ord2->model_pdf = $defaultPdfModel['Commande'];
			$result = $ord2->create($user);
			if ($result > 0) {
				$order2Id = $ord2->id;
				if ($prodEtagereId > 0) {
					$ord2->addline('Etagere murale noyer 120cm', 320, 4, '20.0', 0, 0, $prodEtagereId);
				}
				if ($prodChaiseId > 0) {
					$ord2->addline('Chaise rembourrée tissu gris', 185, 8, '20.0', 0, 0, $prodChaiseId);
				}
				if ($prodBureauId > 0) {
					$ord2->addline('Bureau reglable chene/acier', 680, 4, '20.0', 0, 0, $prodBureauId);
				}
				$ord2->valid($user, $warehouseId);
			}
		}

		// Order 3 — coworking (from signed proposal)
		if ($socClient4Id > 0) {
			$ord3 = new Commande($db);
			$ord3->socid = $socClient4Id;
			$ord3->date_commande = $dateW2;
			$ord3->date = $dateW2;
			$ord3->fk_project = $project3Id;
			$ord3->cond_reglement_id = 1;
			$ord3->mode_reglement_id = 2;
			$ord3->note_private = 'Phase 1 - 25 postes. Commande urgente.';
			$ord3->model_pdf = $defaultPdfModel['Commande'];
			$result = $ord3->create($user);
			if ($result > 0) {
				$order3Id = $ord3->id;
				if ($prodBureauId > 0) {
					$ord3->addline('Bureau reglable chene/acier', 680, 25, '20.0', 0, 0, $prodBureauId);
				}
				if ($prodChaiseId > 0) {
					$ord3->addline('Chaise rembourrée tissu gris', 185, 25, '20.0', 0, 0, $prodChaiseId);
				}
				if ($prodEtagereId > 0) {
					$ord3->addline('Etagere murale noyer 120cm', 320, 10, '20.0', 0, 0, $prodEtagereId);
				}
				$ord3->valid($user, $warehouseId);
			}
		}
	}

	// ========================================================================
	// Phase 11: Customer invoices + payments
	// ========================================================================
	$invoice1Id = 0;

	if (isModEnabled('invoice') && $socClient1Id > 0) {
		_demoLog("Phase 11: Customer invoices");
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

		// Invoice 1 — paid (hotel furniture delivery)
		$inv1 = new Facture($db);
		$inv1->socid = $socClient1Id;
		$inv1->date = $dateM8;
		$inv1->cond_reglement_id = 1;
		$inv1->mode_reglement_id = 2;
		$inv1->fk_project = $project1Id;
		$inv1->type = Facture::TYPE_STANDARD;
		$inv1->note_private = 'Facture mobilier chambres etages 1-3';
		$inv1->model_pdf = $defaultPdfModel['Facture'];
		$result = $inv1->create($user);
		if ($result > 0) {
			$invoice1Id = $inv1->id;
			if ($prodTableId > 0) {
				$inv1->addline('Table en chene massif 180cm', 850, 6, '20.0', 0, 0, $prodTableId);
			}
			if ($prodChaiseId > 0) {
				$inv1->addline('Chaise rembourrée tissu bleu marine', 185, 24, '20.0', 0, 0, $prodChaiseId);
			}
			if ($prodBancId > 0) {
				$inv1->addline('Banc chene massif 160cm', 420, 4, '20.0', 0, 0, $prodBancId);
			}
			$inv1->validate($user);

			$inv1->fetch($inv1->id);
			$pmt = new Paiement($db);
			$pmt->datepaye = $dateM7;
			$pmt->paiementid = 2;
			$pmt->amounts = array($inv1->id => $inv1->total_ttc);
			$pmt->multicurrency_amounts = array();
			$result = $pmt->create($user, 1);
			if ($result > 0 && $bankAccountId > 0) {
				$pmt->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankAccountId, '', '');
			}
		}

		// Invoice 2 — validated, unpaid (installation services hotel)
		$inv2 = new Facture($db);
		$inv2->socid = $socClient1Id;
		$inv2->date = $dateM3;
		$inv2->cond_reglement_id = 1;
		$inv2->mode_reglement_id = 2;
		$inv2->fk_project = $project1Id;
		$inv2->type = Facture::TYPE_STANDARD;
		$inv2->note_private = 'Installation mobilier etages 1-3';
		$inv2->model_pdf = $defaultPdfModel['Facture'];
		$result = $inv2->create($user);
		if ($result > 0) {
			if ($servInstallId > 0) {
				$inv2->addline('Installation et montage (4 demi-journees)', 350, 4, '20.0', 0, 0, $servInstallId);
			}
			if ($servLivraisonId > 0) {
				$inv2->addline('Livraison Cannes (2 trajets)', 150, 2, '20.0', 0, 0, $servLivraisonId);
			}
			$inv2->validate($user);
		}

		// Invoice 3 — draft (architect shelves)
		if ($socClient2Id > 0) {
			$inv3 = new Facture($db);
			$inv3->socid = $socClient2Id;
			$inv3->date = $dateW2;
			$inv3->cond_reglement_id = 1;
			$inv3->mode_reglement_id = 2;
			$inv3->fk_project = $project2Id;
			$inv3->type = Facture::TYPE_STANDARD;
			$inv3->model_pdf = $defaultPdfModel['Facture'];
			$result = $inv3->create($user);
			if ($result > 0) {
				if ($prodEtagereId > 0) {
					$inv3->addline('Etagere murale noyer 120cm', 320, 4, '20.0', 0, 0, $prodEtagereId);
				}
			}
		}

		// Invoice 4 — paid (old invoice to quincaillerie)
		if ($socFourn3Id > 0) {
			$inv4 = new Facture($db);
			$inv4->socid = $socFourn3Id;
			$inv4->date = $dateM6;
			$inv4->cond_reglement_id = 1;
			$inv4->mode_reglement_id = 2;
			$inv4->type = Facture::TYPE_STANDARD;
			$inv4->note_private = 'Vente meuble exposition au gerant';
			$inv4->model_pdf = $defaultPdfModel['Facture'];
			$result = $inv4->create($user);
			if ($result > 0) {
				if ($prodTableId > 0) {
					$inv4->addline('Table en chene massif 180cm (modele expo)', 700, 1, '20.0', 0, 0, $prodTableId);
				}
				if ($prodChaiseId > 0) {
					$inv4->addline('Chaise rembourrée tissu (modele expo x4)', 150, 4, '20.0', 0, 0, $prodChaiseId);
				}
				$inv4->validate($user);

				$inv4->fetch($inv4->id);
				$pmt4 = new Paiement($db);
				$pmt4->datepaye = $dateM5;
				$pmt4->paiementid = 6; // CB
				$pmt4->amounts = array($inv4->id => $inv4->total_ttc);
				$pmt4->multicurrency_amounts = array();
				$result = $pmt4->create($user, 1);
				if ($result > 0 && $bankAccountId > 0) {
					$pmt4->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankAccountId, '', '');
				}
			}
		}

		// Invoice 5 — validated, partially overdue (coworking acompte)
		if ($socClient4Id > 0) {
			$inv5 = new Facture($db);
			$inv5->socid = $socClient4Id;
			$inv5->date = $dateW1;
			$inv5->cond_reglement_id = 1;
			$inv5->mode_reglement_id = 2;
			$inv5->fk_project = $project3Id;
			$inv5->type = Facture::TYPE_STANDARD;
			$inv5->note_private = 'Acompte 50% commande coworking';
			$inv5->model_pdf = $defaultPdfModel['Facture'];
			$result = $inv5->create($user);
			if ($result > 0) {
				if ($prodBureauId > 0) {
					$inv5->addline('Acompte bureaux reglables (25 unites)', 340, 25, '20.0', 0, 0, $prodBureauId);
				}
				$inv5->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 12: Supplier orders + invoices
	// ========================================================================
	if (isModEnabled('supplier_order') && $socFourn1Id > 0) {
		_demoLog("Phase 12: Supplier orders and invoices");
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

		// Supplier order 1 — wood (approved, received)
		$sord1 = new CommandeFournisseur($db);
		$sord1->socid = $socFourn1Id;
		$sord1->date_commande = $dateM10;
		$sord1->note_private = 'Commande annuelle bois massif';
		$sord1->model_pdf = $defaultPdfModel['CommandeFournisseur'];
		$result = $sord1->create($user);
		if ($result > 0) {
			$sord1->addline('Lot planches chene brut 200x30x3 cm (x80)', 45, 80, '20.0');
			$sord1->addline('Lot planches noyer brut 150x25x3 cm (x40)', 55, 40, '20.0');
			$sord1->valid($user);
			$sord1->approve($user);
		}

		// Supplier order 2 — fabric (approved)
		if ($socFourn2Id > 0) {
			$sord2 = new CommandeFournisseur($db);
			$sord2->socid = $socFourn2Id;
			$sord2->date_commande = $dateM4;
			$sord2->model_pdf = $defaultPdfModel['CommandeFournisseur'];
			$result = $sord2->create($user);
			if ($result > 0) {
				$sord2->addline('Tissu ameublement bleu marine (50m)', 25, 50, '20.0');
				$sord2->addline('Tissu ameublement gris anthracite (30m)', 25, 30, '20.0');
				$sord2->addline('Mousse haute densite 5cm (30 plaques)', 18, 30, '20.0');
				$sord2->valid($user);
				$sord2->approve($user);
			}
		}

		// Supplier order 3 — hardware (validated, not approved)
		if ($socFourn3Id > 0) {
			$sord3 = new CommandeFournisseur($db);
			$sord3->socid = $socFourn3Id;
			$sord3->date_commande = $dateW1;
			$sord3->note_private = 'Reapprovisionnement quincaillerie pour commande coworking';
			$sord3->model_pdf = $defaultPdfModel['CommandeFournisseur'];
			$result = $sord3->create($user);
			if ($result > 0) {
				$sord3->addline('Kit quincaillerie assemblage (x50)', 12, 50, '20.0');
				$sord3->addline('Vis inox tete fraisee 5x40 (boite 500)', 15, 10, '20.0');
				$sord3->valid($user);
			}
		}

		// Supplier order 4 — varnish
		if ($socFourn4Id > 0) {
			$sord4 = new CommandeFournisseur($db);
			$sord4->socid = $socFourn4Id;
			$sord4->date_commande = $dateM2;
			$sord4->model_pdf = $defaultPdfModel['CommandeFournisseur'];
			$result = $sord4->create($user);
			if ($result > 0) {
				$sord4->addline('Vernis mat incolore ecolabel (5L)', 35, 10, '20.0');
				$sord4->addline('Huile de protection chene (2.5L)', 28, 8, '20.0');
				$sord4->valid($user);
				$sord4->approve($user);
			}
		}

		// Supplier invoices
		if (isModEnabled('supplier_invoice')) {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

			// Supplier invoice 1 — paid (wood)
			$sinv1 = new FactureFournisseur($db);
			$sinv1->socid = $socFourn1Id;
			$sinv1->date = $dateM9;
			$sinv1->ref_supplier = 'FAC-BMN-2025-042';
			$sinv1->cond_reglement_id = 1;
			$sinv1->mode_reglement_id = 2;
			$sinv1->model_pdf = $defaultPdfModel['FactureFournisseur'];
			$result = $sinv1->create($user);
			if ($result > 0) {
				$sinv1->addline('Lot planches chene brut (x80)', 45, '20.0', 0, 0, 80);
				$sinv1->addline('Lot planches noyer brut (x40)', 55, '20.0', 0, 0, 40);
				$sinv1->validate($user);

				$sinv1->fetch($sinv1->id);
				$spmt = new PaiementFourn($db);
				$spmt->datepaye = $dateM8;
				$spmt->paiementid = 2;
				$spmt->amounts = array($sinv1->id => $sinv1->total_ttc);
				$spmt->multicurrency_amounts = array();
				// PaiementFourn::create()/addPaymentToBank() read multicurrency_code/tx
				// keyed by invoice id without isset() guards (unlike the customer
				// Paiement class). In our mono-currency demo, seed them explicitly.
				$spmt->multicurrency_code = array($sinv1->id => ($sinv1->multicurrency_code ? $sinv1->multicurrency_code : getDolGlobalString('MAIN_MONNAIE', 'EUR')));
				$spmt->multicurrency_tx = array($sinv1->id => 1);
				$result = $spmt->create($user, 1);
				if ($result > 0 && $bankAccountId > 0) {
					$spmt->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $bankAccountId, '', '');
				}
			}

			// Supplier invoice 2 — unpaid (fabric)
			if ($socFourn2Id > 0) {
				$sinv2 = new FactureFournisseur($db);
				$sinv2->socid = $socFourn2Id;
				$sinv2->date = $dateM3;
				$sinv2->ref_supplier = 'TXB-2025-117';
				$sinv2->cond_reglement_id = 1;
				$sinv2->mode_reglement_id = 2;
				$sinv2->model_pdf = $defaultPdfModel['FactureFournisseur'];
				$result = $sinv2->create($user);
				if ($result > 0) {
					$sinv2->addline('Tissu bleu marine (50m)', 25, '20.0', 0, 0, 50);
					$sinv2->addline('Tissu gris anthracite (30m)', 25, '20.0', 0, 0, 30);
					$sinv2->addline('Mousse haute densite (30 plaques)', 18, '20.0', 0, 0, 30);
					$sinv2->validate($user);
				}
			}

			// Supplier invoice 3 — paid (varnish)
			if ($socFourn4Id > 0) {
				$sinv3 = new FactureFournisseur($db);
				$sinv3->socid = $socFourn4Id;
				$sinv3->date = $dateM1;
				$sinv3->ref_supplier = 'VPF-26-0089';
				$sinv3->cond_reglement_id = 1;
				$sinv3->mode_reglement_id = 2;
				$sinv3->model_pdf = $defaultPdfModel['FactureFournisseur'];
				$result = $sinv3->create($user);
				if ($result > 0) {
					$sinv3->addline('Vernis mat incolore ecolabel (5L x10)', 35, '20.0', 0, 0, 10);
					$sinv3->addline('Huile protection chene (2.5L x8)', 28, '20.0', 0, 0, 8);
					$sinv3->validate($user);

					$sinv3->fetch($sinv3->id);
					$spmt3 = new PaiementFourn($db);
					$spmt3->datepaye = $dateW2;
					$spmt3->paiementid = 2;
					$spmt3->amounts = array($sinv3->id => $sinv3->total_ttc);
					$spmt3->multicurrency_amounts = array();
					// See note on $spmt above: seed multicurrency_code/tx (mono-currency demo).
					$spmt3->multicurrency_code = array($sinv3->id => ($sinv3->multicurrency_code ? $sinv3->multicurrency_code : getDolGlobalString('MAIN_MONNAIE', 'EUR')));
					$spmt3->multicurrency_tx = array($sinv3->id => 1);
					$result = $spmt3->create($user, 1);
					if ($result > 0 && $bankAccountId > 0) {
						$spmt3->addPaymentToBank($user, 'payment_supplier', '(SupplierInvoicePayment)', $bankAccountId, '', '');
					}
				}
			}
		}
	}

	// ========================================================================
	// Phase 13: Shipments
	// ========================================================================
	if (isModEnabled('shipping') && $warehouseId > 0) {
		_demoLog("Phase 13: Shipments");
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

		// Shipment 1 — order 1 (hotel)
		if ($order1Id > 0) {
			$ship = new Expedition($db);
			$ship->socid = $socClient1Id;
			$ship->date_delivery = $dateM7;
			$ship->origin = 'commande';
			$ship->origin_id = $order1Id;
			$ship->note_private = 'Livraison lot 1 - mobilier chambres';
			$ship->model_pdf = $defaultPdfModel['Expedition'];
			$result = $ship->create($user);
			if ($result > 0) {
				$ordForShip = new Commande($db);
				$ordForShip->fetch($order1Id);
				$ordForShip->fetch_lines();
				if (!empty($ordForShip->lines)) {
					foreach ($ordForShip->lines as $line) {
						if ($line->product_type == 0 && $line->fk_product > 0) {
							$ship->addline($warehouseId, $line->id, $line->qty);
						}
					}
				}
				$ship->valid($user);
			}
		}

		// Shipment 2 — order 2 (architect partial)
		if ($order2Id > 0) {
			$ship2 = new Expedition($db);
			$ship2->socid = $socClient2Id;
			$ship2->date_delivery = $dateW1;
			$ship2->origin = 'commande';
			$ship2->origin_id = $order2Id;
			$ship2->note_private = 'Livraison partielle - etageres uniquement, bureaux en fabrication';
			$ship2->model_pdf = $defaultPdfModel['Expedition'];
			$result = $ship2->create($user);
			if ($result > 0) {
				$ordForShip2 = new Commande($db);
				$ordForShip2->fetch($order2Id);
				$ordForShip2->fetch_lines();
				if (!empty($ordForShip2->lines)) {
					foreach ($ordForShip2->lines as $line) {
						if ($line->product_type == 0 && $line->fk_product == $prodEtagereId) {
							$ship2->addline($warehouseId, $line->id, $line->qty);
						}
					}
				}
				$ship2->valid($user);
			}
		}
	}

	// ========================================================================
	// Phase 14: Contracts
	// ========================================================================
	if (isModEnabled('contract') && $socClient1Id > 0) {
		_demoLog("Phase 14: Contracts");
		require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

		// Contract 1 — hotel maintenance
		$contract = new Contrat($db);
		$contract->socid = $socClient1Id;
		$contract->date_contrat = $dateM6;
		$contract->commercial_signature_id = $user->id;
		$contract->commercial_suivi_id = $user1Id > 0 ? $user1Id : $user->id;
		$contract->fk_project = $project1Id;
		$contract->note_private = 'Contrat annuel reconductible. Visite trimestrielle incluse.';
		$contract->model_pdf = $defaultPdfModel['Contrat'];
		$result = $contract->create($user);
		if ($result > 0) {
			$dateContratEnd = dol_time_plus_duree($dateM6, 12, 'm');
			$contract->addline(
				'Contrat entretien mobilier - forfait mensuel',
				80, 1, '20.0', 0, 0,
				$servEntretienId > 0 ? $servEntretienId : 0,
				0, $dateM6, $dateContratEnd, 'HT', 0, 0
			);
			$contract->validate($user);
			$contract->fetch($contract->id);
			$contract->fetch_lines();
			if (!empty($contract->lines)) {
				$contract->active_line($user, $contract->lines[0]->id, $dateM6, $dateContratEnd);
			}
		}

		// Contract 2 — coworking maintenance
		if ($socClient4Id > 0) {
			$contract2 = new Contrat($db);
			$contract2->socid = $socClient4Id;
			$contract2->date_contrat = $dateW1;
			$contract2->commercial_signature_id = $user->id;
			$contract2->commercial_suivi_id = $user1Id > 0 ? $user1Id : $user->id;
			$contract2->fk_project = $project3Id;
			$contract2->note_private = 'Contrat maintenance incluant remplacement pieces defectueuses sous garantie.';
			$contract2->model_pdf = $defaultPdfModel['Contrat'];
			$result = $contract2->create($user);
			if ($result > 0) {
				$dateContrat2End = dol_time_plus_duree($dateW1, 24, 'm');
				$contract2->addline(
					'Maintenance mobilier coworking - forfait trimestriel',
					250, 1, '20.0', 0, 0,
					$servEntretienId > 0 ? $servEntretienId : 0,
					0, $dateW1, $dateContrat2End, 'HT', 0, 0
				);
				$contract2->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 15: Interventions
	// ========================================================================
	if (isModEnabled('intervention') && $socClient1Id > 0) {
		_demoLog("Phase 15: Interventions");
		require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

		$fi1 = new Fichinter($db);
		$fi1->socid = $socClient1Id;
		$fi1->date = $dateM5;
		$fi1->datec = $dateM5;
		$fi1->fk_projet = $project1Id;
		$fi1->description = 'Installation mobilier chambres etage 1 - lot 1 (30 chambres)';
		$fi1->note_private = 'Equipe : Marie Martin + 1 intérimaire. Acces par monte-charge.';
		$fi1->model_pdf = $defaultPdfModel['Fichinter'];
		$result = $fi1->create($user);
		if ($result > 0) {
			$fi1->addline($user, $fi1->id, 'Montage tables de nuit et bureaux chambres 101-115', $dateM5, 14400);
			$fi1->addline($user, $fi1->id, 'Montage tables de nuit et bureaux chambres 116-130', dol_time_plus_duree($dateM5, 1, 'd'), 14400);
			$fi1->setValid($user);
		}

		$fi2 = new Fichinter($db);
		$fi2->socid = $socClient1Id;
		$fi2->date = $dateM3;
		$fi2->datec = $dateM3;
		$fi2->fk_projet = $project1Id;
		$fi2->description = 'Installation mobilier espaces communs - hall et restaurant';
		$fi2->model_pdf = $defaultPdfModel['Fichinter'];
		$result = $fi2->create($user);
		if ($result > 0) {
			$fi2->addline($user, $fi2->id, 'Installation bancs et tables hall d\'accueil', $dateM3, 10800);
			$fi2->addline($user, $fi2->id, 'Installation tables et chaises restaurant', dol_time_plus_duree($dateM3, 1, 'd'), 14400);
			$fi2->setValid($user);
		}

		if ($socClient2Id > 0) {
			$fi3 = new Fichinter($db);
			$fi3->socid = $socClient2Id;
			$fi3->date = $dateW1;
			$fi3->datec = $dateW1;
			$fi3->fk_projet = $project2Id;
			$fi3->description = 'Pose etageres murales et livraison premiere tranche';
			$fi3->model_pdf = $defaultPdfModel['Fichinter'];
			$result = $fi3->create($user);
			if ($result > 0) {
				$fi3->addline($user, $fi3->id, 'Pose de 4 etageres murales noyer avec fixations invisibles', $dateW1, 10800);
				$fi3->setValid($user);
			}
		}
	}

	// ========================================================================
	// Phase 16: Tickets
	// ========================================================================
	if (isModEnabled('ticket')) {
		_demoLog("Phase 16: Tickets");
		require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';

		// Ticket 1 — closed (resolved scratch issue)
		$tick1 = new Ticket($db);
		$tick1->fk_soc = $socClient1Id;
		$tick1->subject = 'Rayure sur plateau de table livree chambre 112';
		$tick1->message = 'Bonjour, nous avons constate une rayure visible sur le plateau de la table ref. MEUB-TABLE-CH livree dans la chambre 112 la semaine derniere. La rayure fait environ 15 cm. Merci de nous indiquer la procedure a suivre pour un remplacement ou une reparation.';
		$tick1->type_code = 'COM';
		$tick1->severity_code = 'NORMAL';
		$tick1->fk_project = $project1Id;
		$tick1->datec = $dateM4;
		$tick1->ref = $tick1->getDefaultRef();
		$result = $tick1->create($user);
		if ($result > 0) {
			$tick1->setStatut(8); // STATUS_CLOSED
		}

		// Ticket 2 — open, read (additional quote request)
		$tick2 = new Ticket($db);
		$tick2->fk_soc = $socClient2Id;
		$tick2->subject = 'Demande de devis pour 4 bureaux supplementaires';
		$tick2->message = 'Bonjour, suite a notre projet de renovation, nous aimerions recevoir un devis pour 4 bureaux reglables supplementaires, meme modele que ceux commandes. Nous pensons aussi ajouter des caissons de rangement si vous en proposez. Merci de nous recontacter.';
		$tick2->type_code = 'COM';
		$tick2->severity_code = 'LOW';
		$tick2->fk_project = $project2Id;
		$tick2->datec = $dateW1;
		$tick2->ref = $tick2->getDefaultRef();
		$result = $tick2->create($user);
		if ($result > 0) {
			$tick2->setStatut(3); // STATUS_READ
		}

		// Ticket 3 — open, new (delivery issue coworking)
		$tick3 = new Ticket($db);
		$tick3->fk_soc = $socClient4Id;
		$tick3->subject = 'Retard livraison bureaux commande coworking';
		$tick3->message = 'Bonjour, nous devions recevoir la livraison des 25 bureaux reglables cette semaine mais n\'avons aucune nouvelle. Notre ouverture est prevue dans 3 semaines, c\'est urgent. Merci de nous donner un point sur le planning.';
		$tick3->type_code = 'COM';
		$tick3->severity_code = 'HIGH';
		$tick3->fk_project = $project3Id;
		$tick3->datec = $dateD3;
		$tick3->ref = $tick3->getDefaultRef();
		$tick3->create($user);

		// Ticket 4 — closed (maintenance question)
		$tick4 = new Ticket($db);
		$tick4->fk_soc = $socClient1Id;
		$tick4->subject = 'Conseils entretien tables chene';
		$tick4->message = 'Bonjour, notre equipe d\'entretien souhaite connaitre les produits recommandes pour l\'entretien quotidien des tables en chene dans les chambres. Y a-t-il des produits a eviter absolument ?';
		$tick4->type_code = 'COM';
		$tick4->severity_code = 'LOW';
		$tick4->fk_project = $project1Id;
		$tick4->datec = $dateM2;
		$tick4->ref = $tick4->getDefaultRef();
		$result = $tick4->create($user);
		if ($result > 0) {
			$tick4->setStatut(8); // STATUS_CLOSED
		}
	}

	// ========================================================================
	// Phase 17: Expense reports
	// ========================================================================
	if (isModEnabled('expensereport')) {
		_demoLog("Phase 17: Expense reports");
		require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

		// Expense report 1 — Jean Dupont (trip to Cannes)
		if ($user1Id > 0) {
			$exp = new ExpenseReport($db);
			$exp->fk_user_author = $user1Id;
			$exp->fk_user_validator = $user->id;
			$exp->date_debut = $dateM5;
			$exp->date_fin = dol_time_plus_duree($dateM5, 3, 'd');
			$result = $exp->create($user);
			if ($result > 0) {
				$feeTrip = _demoGetDictId($db, 'c_type_fees', 'id', "code = 'TF_TRIP'");
				$feeLunch = _demoGetDictId($db, 'c_type_fees', 'id', "code = 'TF_LUNCH'");

				if ($feeTrip > 0) {
					$exp->addline(1, 125.00, $feeTrip, '20.0', $dateM5, 'Train Paris-Cannes A/R');
				}
				if ($feeLunch > 0) {
					$exp->addline(2, 22.00, $feeLunch, '10.0', $dateM5, 'Dejeuner client Hotel Le Grand Bleu');
					$exp->addline(1, 18.50, $feeLunch, '10.0', dol_time_plus_duree($dateM5, 1, 'd'), 'Dejeuner sur site');
				}
			}
		}

		// Expense report 2 — Marie Martin (trip to Lyon)
		if ($user2Id > 0) {
			$exp2 = new ExpenseReport($db);
			$exp2->fk_user_author = $user2Id;
			$exp2->fk_user_validator = $user->id;
			$exp2->date_debut = $dateM2;
			$exp2->date_fin = dol_time_plus_duree($dateM2, 2, 'd');
			$result = $exp2->create($user);
			if ($result > 0) {
				$feeTrip = _demoGetDictId($db, 'c_type_fees', 'id', "code = 'TF_TRIP'");
				$feeLunch = _demoGetDictId($db, 'c_type_fees', 'id', "code = 'TF_LUNCH'");

				if ($feeTrip > 0) {
					$exp2->addline(1, 95.00, $feeTrip, '20.0', $dateM2, 'Train Paris-Lyon A/R');
				}
				if ($feeLunch > 0) {
					$exp2->addline(1, 16.00, $feeLunch, '10.0', $dateM2, 'Dejeuner Cabinet Moreau');
				}
			}
		}
	}

	// ========================================================================
	// Phase 18: Holidays
	// ========================================================================
	if (isModEnabled('holiday')) {
		_demoLog("Phase 18: Holidays");
		require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';

		$leaveTypeId = _demoGetDictId($db, 'c_holiday_types', 'rowid', "active = 1");

		// Holiday 1 — Marie Martin summer
		if ($leaveTypeId > 0 && $user2Id > 0) {
			$hol = new Holiday($db);
			$hol->fk_user = $user2Id;
			$hol->fk_validator = $user->id;
			$hol->fk_type = $leaveTypeId;
			$hol->date_debut = $dateP1;
			$hol->date_fin = dol_time_plus_duree($dateP1, 10, 'd');
			$hol->halfday = 0;
			$hol->description = 'Vacances ete - 2 semaines';
			$hol->entity = $conf->entity;
			$result = $hol->create($user);
			if ($result > 0) {
				$hol->validate($user);
			}
		}

		// Holiday 2 — Jean Dupont short break
		if ($leaveTypeId > 0 && $user1Id > 0) {
			$hol2 = new Holiday($db);
			$hol2->fk_user = $user1Id;
			$hol2->fk_validator = $user->id;
			$hol2->fk_type = $leaveTypeId;
			$hol2->date_debut = $dateP2;
			$hol2->date_fin = dol_time_plus_duree($dateP2, 4, 'd');
			$hol2->halfday = 0;
			$hol2->description = 'Pont + week-end prolonge';
			$hol2->entity = $conf->entity;
			$result = $hol2->create($user);
			if ($result > 0) {
				$hol2->validate($user);
			}
		}

		// Holiday 3 — Philippe Durand (past, taken)
		if ($leaveTypeId > 0 && $user3Id > 0) {
			$hol3 = new Holiday($db);
			$hol3->fk_user = $user3Id;
			$hol3->fk_validator = $user->id;
			$hol3->fk_type = $leaveTypeId;
			$hol3->date_debut = $dateM2;
			$hol3->date_fin = dol_time_plus_duree($dateM2, 5, 'd');
			$hol3->halfday = 0;
			$hol3->description = 'Vacances ski';
			$hol3->entity = $conf->entity;
			$result = $hol3->create($user);
			if ($result > 0) {
				$hol3->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 19: Donations
	// ========================================================================
	if (isModEnabled('don')) {
		_demoLog("Phase 19: Donations");
		require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

		$don = new Don($db);
		$don->firstname = 'Jacques';
		$don->lastname = 'Petit';
		$don->societe = '';
		$don->amount = 100;
		$don->date = $dateM4;
		$don->public = 1;
		$don->fk_payment = 2;
		$don->address = '5 rue de la Paix';
		$don->zip = '75002';
		$don->town = 'Paris';
		$don->country_id = 1;
		$don->note_private = 'Don pour soutien artisanat local - recu lors des journees du patrimoine';
		$result = $don->create($user);
		if ($result > 0) {
			$don->setValid($user);
		}

		$don2 = new Don($db);
		$don2->firstname = 'Marie-Claude';
		$don2->lastname = 'Fontaine';
		$don2->societe = 'Association Les Amis du Bois';
		$don2->amount = 250;
		$don2->date = $dateM1;
		$don2->public = 1;
		$don2->fk_payment = 2;
		$don2->address = '10 rue des Arts';
		$don2->zip = '75004';
		$don2->town = 'Paris';
		$don2->country_id = 1;
		$don2->note_private = 'Don association - en echange visites atelier pour les adherents';
		$result = $don2->create($user);
		if ($result > 0) {
			$don2->setValid($user);
		}
	}

	// ========================================================================
	// Phase 20: Members
	// ========================================================================
	if (isModEnabled('member')) {
		_demoLog("Phase 20: Members");
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

		$memberType = new AdherentType($db);
		$memberType->label = 'Ami de la Maison Durand';
		$memberType->morphy = 'phy';
		$memberType->subscription = 1;
		$memberType->amount = '50';
		$memberType->status = 1;
		$memberType->note = 'Acces aux ventes privees, invitation aux evenements atelier, reduction 10% sur les services.';
		$result = $memberType->create($user);
		$memberTypeId = ($result > 0) ? $memberType->id : 0;

		if ($memberTypeId > 0) {
			$members = array(
				array('Bernard', 'Alain', 'abernard', 'alain.bernard@example.com', 'Paris', $dateM10),
				array('Roux', 'Isabelle', 'iroux', 'isabelle.roux@example.com', 'Lyon', $dateM6),
				array('Cohen', 'David', 'dcohen', 'david.cohen@example.com', 'Marseille', $dateM3),
				array('Lefebvre', 'Catherine', 'clefebvre', 'catherine.lefebvre@example.com', 'Toulouse', $dateM1),
			);
			foreach ($members as $m) {
				$member = new Adherent($db);
				$member->typeid = $memberTypeId;
				$member->morphy = 'phy';
				$member->lastname = $m[0];
				$member->firstname = $m[1];
				$member->login = $m[2];
				$member->email = $m[3];
				$member->town = $m[4];
				$member->country_id = 1;
				$result = $member->create($user);
				if ($result > 0) {
					$member->validate($user);
					$member->subscription($m[5], 50, ($bankAccountId > 0 ? $bankAccountId : 0), '', 'Cotisation annuelle');
				}
			}
		}
	}

	// ========================================================================
	// Phase 21: BOM (Bill of Materials)
	// ========================================================================
	$bomId = 0;
	$bomId2 = 0;

	if (isModEnabled('bom') && $prodTableId > 0 && $matCheneId > 0) {
		_demoLog("Phase 21: BOM");
		require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';

		// BOM 1 — Oak table
		$bom = new BOM($db);
		$bom->label = 'Nomenclature Table chene 180cm';
		$bom->fk_product = $prodTableId;
		$bom->qty = 1;
		$bom->fk_warehouse = $warehouseId;
		$bom->note_private = 'Temps de fabrication moyen : 16h. Sechage vernis 48h supplementaires.';
		$result = $bom->create($user);
		if ($result > 0) {
			$bomId = $bom->id;
			$bom->addLine($matCheneId, 8, 0, 0, 1.0);
			if ($matQuincailleId > 0) {
				$bom->addLine($matQuincailleId, 1, 0, 0, 1.0);
			}
			$bom->validate($user);
		}

		// BOM 2 — Walnut shelf
		if ($prodEtagereId > 0 && $matNoyerId > 0) {
			$bom2 = new BOM($db);
			$bom2->label = 'Nomenclature Etagere noyer 120cm';
			$bom2->fk_product = $prodEtagereId;
			$bom2->qty = 1;
			$bom2->fk_warehouse = $warehouseId;
			$result = $bom2->create($user);
			if ($result > 0) {
				$bomId2 = $bom2->id;
				$bom2->addLine($matNoyerId, 3, 0, 0, 1.0);
				if ($matQuincailleId > 0) {
					$bom2->addLine($matQuincailleId, 1, 0, 0, 1.0);
				}
				$bom2->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 22: Manufacturing orders (MRP)
	// ========================================================================
	if (isModEnabled('mrp')) {
		_demoLog("Phase 22: MRP");
		require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';

		// MO 1 — tables for hotel (completed)
		if ($bomId > 0 && $prodTableId > 0) {
			$mo = new Mo($db);
			$mo->fk_bom = $bomId;
			$mo->fk_product = $prodTableId;
			$mo->qty = 6;
			$mo->fk_warehouse = $warehouseId;
			$mo->date_start_planned = $dateM9;
			$mo->date_end_planned = $dateM8;
			$mo->note_private = 'Lot tables hotel Grand Bleu';
			$result = $mo->create($user);
			if ($result > 0) {
				$mo->validate($user);
			}
		}

		// MO 2 — shelves for architect (in progress)
		if ($bomId2 > 0 && $prodEtagereId > 0) {
			$mo2 = new Mo($db);
			$mo2->fk_bom = $bomId2;
			$mo2->fk_product = $prodEtagereId;
			$mo2->qty = 10;
			$mo2->fk_warehouse = $warehouseId;
			$mo2->date_start_planned = $dateW2;
			$mo2->date_end_planned = $dateP1;
			$mo2->note_private = 'Lot etageres cabinet Moreau + stock showroom';
			$result = $mo2->create($user);
			if ($result > 0) {
				$mo2->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 23: Recruitment
	// ========================================================================
	if (isModEnabled('recruitment')) {
		_demoLog("Phase 23: Recruitment");
		require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
		require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';

		// Job 1 — cabinetmaker
		$job = new RecruitmentJobPosition($db);
		$job->label = 'Ebeniste qualifie(e)';
		$job->qty = 1;
		$job->fk_user_recruiter = $user->id;
		$job->date_planned = $dateP2;
		$job->remuneration_suggested = '2500-3000 EUR brut/mois';
		$job->description = 'Nous recherchons un(e) ebeniste qualifie(e) pour renforcer notre atelier. Maitrise du travail du chene et du noyer exigee. Experience en fabrication de meubles sur mesure souhaitee.';
		$job->status = 1;
		$result = $job->create($user);
		if ($result > 0) {
			$cand1 = new RecruitmentCandidature($db);
			$cand1->fk_recruitmentjobposition = $job->id;
			$cand1->lastname = 'Lefevre';
			$cand1->firstname = 'Antoine';
			$cand1->email = 'antoine.lefevre@example.com';
			$cand1->phone = '06 12 34 56 78';
			$cand1->description = '10 ans d\'experience en ebenisterie traditionnelle. Maitrise du travail du chene et du noyer. CAP + BP ebenisterie. Dernier poste : atelier Maison Gervais (5 ans).';
			$cand1->create($user);

			$cand2 = new RecruitmentCandidature($db);
			$cand2->fk_recruitmentjobposition = $job->id;
			$cand2->lastname = 'Boucher';
			$cand2->firstname = 'Sylvain';
			$cand2->email = 'sylvain.boucher@example.com';
			$cand2->phone = '06 99 88 77 66';
			$cand2->description = '3 ans d\'experience. Formation compagnons du devoir. Specialise bois massif et marqueterie.';
			$cand2->create($user);
		}

		// Job 2 — delivery driver
		$job2 = new RecruitmentJobPosition($db);
		$job2->label = 'Livreur / installateur';
		$job2->qty = 1;
		$job2->fk_user_recruiter = $user->id;
		$job2->date_planned = $dateP1;
		$job2->remuneration_suggested = '2000-2200 EUR brut/mois';
		$job2->description = 'Livraison et installation de mobilier chez nos clients. Permis B obligatoire, permis C apprecie. Bonne condition physique. Sens du contact client.';
		$job2->status = 1;
		$job2->create($user);
	}

	// ========================================================================
	// Phase 24: Salaries
	// ========================================================================
	if (isModEnabled('salaries') && ($user1Id > 0 || $user2Id > 0)) {
		_demoLog("Phase 24: Salaries");
		require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';

		$year = (int) dol_print_date($now, '%Y');

		$salaryData = array();
		if ($user1Id > 0) {
			$salaryData[] = array($user1Id, 2800, 'Jean Dupont');
		}
		if ($user2Id > 0) {
			$salaryData[] = array($user2Id, 2400, 'Marie Martin');
		}
		if ($user3Id > 0) {
			$salaryData[] = array($user3Id, 3200, 'Philippe Durand');
		}

		foreach ($salaryData as $sd) {
			for ($month = 1; $month <= 3; $month++) {
				$sal = new Salary($db);
				$sal->fk_user = $sd[0];
				$sal->amount = $sd[1];
				$sal->label = 'Salaire '.dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), '%B').' '.$year.' - '.$sd[2];
				$sal->datesp = dol_mktime(0, 0, 0, $month, 1, $year);
				$lastday = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
				$sal->dateep = dol_mktime(23, 59, 59, $month, $lastday, $year);
				$sal->type_payment = 3;
				$sal->fk_project = $project1Id > 0 ? $project1Id : -1;
				$sal->accountid = $bankAccountId > 0 ? $bankAccountId : 0;
				$sal->create($user);
			}
		}
	}

	// ========================================================================
	// Phase 25: Agenda events
	// ========================================================================
	if (isModEnabled('agenda')) {
		_demoLog("Phase 25: Agenda events");
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		$agendaEvents = array(
			array('label' => 'Appel Sophie Leclerc - point avancement hotel', 'type_code' => 'AC_TEL', 'datep' => $dateM6, 'socid' => $socClient1Id, 'fk_project' => $project1Id, 'note' => 'Retour positif sur les premieres livraisons. Demande ajout 4 bancs pour le hall.', 'percent' => 100, 'owner' => $user1Id),
			array('label' => 'Visite showroom - Camille Garnier (La Belle Epoque)', 'type_code' => 'AC_RDV', 'datep' => $dateM3, 'socid' => $socClient3Id, 'note' => 'Visite showroom avec la gerante. Tres interessee par les tables chene et les chaises. A relancer pour devis.', 'percent' => 100, 'owner' => $user1Id),
			array('label' => 'RDV prise de mesures Cabinet Moreau', 'type_code' => 'AC_RDV', 'datep' => $dateM2, 'socid' => $socClient2Id, 'fk_project' => $project2Id, 'note' => 'Mesures des 3 bureaux et salle de reunion. Pierre Moreau souhaite du noyer pour les etageres.', 'percent' => 100, 'owner' => $user2Id),
			array('label' => 'Relance devis Cabinet Moreau', 'type_code' => 'AC_TEL', 'datep' => $dateW2, 'socid' => $socClient2Id, 'fk_project' => $project2Id, 'note' => 'Pierre Moreau demande un delai de reflexion supplementaire. Relancer dans 1 semaine.', 'percent' => 100, 'owner' => $user1Id),
			array('label' => 'Appel WorkSpace Republique - lancement projet', 'type_code' => 'AC_TEL', 'datep' => $dateW3, 'socid' => $socClient4Id, 'fk_project' => $project3Id, 'note' => 'Linh Nguyen confirme commande 25 postes phase 1. Devis signe. Urgence livraison.', 'percent' => 100, 'owner' => $user1Id),
			array('label' => 'Relance prospect Mairie Vincennes', 'type_code' => 'AC_TEL', 'datep' => $dateP1, 'socid' => $socClient5Id, 'note' => 'Rappeler Anne Rousseau pour nouveau devis mediatheque en septembre.', 'percent' => 0, 'owner' => $user1Id),
			array('label' => 'Visite atelier Bois & Materiaux du Nord', 'type_code' => 'AC_RDV', 'datep' => $dateP2, 'socid' => $socFourn1Id, 'note' => 'Visite annuelle fournisseur pour negociation tarifs et selection nouveaux bois.', 'percent' => 0, 'owner' => $user3Id),
			array('label' => 'Reunion equipe - planning ete', 'type_code' => 'AC_RDV', 'datep' => $dateD1, 'note' => 'Point sur les commandes en cours, planning conges ete, priorites fabrication.', 'percent' => 0, 'owner' => $user->id),
		);

		foreach ($agendaEvents as $evt) {
			$action = new ActionComm($db);
			$action->label = $evt['label'];
			$action->type_code = $evt['type_code'];
			$action->datep = $evt['datep'];
			$action->datef = $evt['datep'];
			$action->socid = !empty($evt['socid']) ? $evt['socid'] : 0;
			$action->fk_project = !empty($evt['fk_project']) ? $evt['fk_project'] : 0;
			$action->note_private = !empty($evt['note']) ? $evt['note'] : '';
			$action->percentage = $evt['percent'];
			$action->userownerid = !empty($evt['owner']) ? $evt['owner'] : $user->id;
			$action->fulldayevent = 0;
			$action->create($user);
		}
	}

	// ========================================================================
	// Phase 26: HRM — Skills, Jobs, Evaluations
	// ========================================================================
	if (isModEnabled('hrm')) {
		_demoLog("Phase 26: HRM (skills, jobs, evaluations)");
		require_once DOL_DOCUMENT_ROOT.'/hrm/class/skill.class.php';
		require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
		require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluation.class.php';

		$skill1Id = 0;
		$skill2Id = 0;
		$skill3Id = 0;
		$skill4Id = 0;
		$skill5Id = 0;

		$sk1 = new Skill($db);
		$sk1->label = 'Ebenisterie';
		$sk1->description = 'Travail du bois massif, assemblages traditionnels, finitions';
		$sk1->skill_type = 0; // KnowHow
		$result = $sk1->create($user);
		if ($result > 0) {
			$skill1Id = $result;
			// No validate() — Dolibarr lacks mod_skill_standard numbering module
		}

		$sk2 = new Skill($db);
		$sk2->label = 'Tapisserie ameublement';
		$sk2->description = 'Garnissage, couture, pose de tissu sur sieges et assises';
		$sk2->skill_type = 0;
		$result = $sk2->create($user);
		if ($result > 0) {
			$skill2Id = $result;
			// No validate() — Dolibarr lacks mod_skill_standard numbering module
		}

		$sk3 = new Skill($db);
		$sk3->label = 'Relation client';
		$sk3->description = 'Ecoute, conseil, suivi projet, gestion des reclamations';
		$sk3->skill_type = 1; // HowToBe
		$result = $sk3->create($user);
		if ($result > 0) {
			$skill3Id = $result;
			// No validate() — Dolibarr lacks mod_skill_standard numbering module
		}

		$sk4 = new Skill($db);
		$sk4->label = 'Normes securite atelier';
		$sk4->description = 'Reglementation EPI, utilisation machines-outils, protocoles incendie';
		$sk4->skill_type = 9; // Knowledge
		$result = $sk4->create($user);
		if ($result > 0) {
			$skill4Id = $result;
			// No validate() — Dolibarr lacks mod_skill_standard numbering module
		}

		$sk5 = new Skill($db);
		$sk5->label = 'Lecture de plans';
		$sk5->description = 'Interpretation plans techniques, cotes, vues eclatees';
		$sk5->skill_type = 0;
		$result = $sk5->create($user);
		if ($result > 0) {
			$skill5Id = $result;
			// No validate() — Dolibarr lacks mod_skill_standard numbering module
		}

		$job1Id = 0;
		$job2Id = 0;
		$job3Id = 0;

		$job1 = new Job($db);
		$job1->label = 'Ebeniste';
		$job1->description = 'Fabrication et assemblage de meubles en bois massif';
		$result = $job1->create($user);
		if ($result > 0) {
			$job1Id = $result;
			// No validate() — Dolibarr lacks mod_job_standard numbering module
		}

		$job2 = new Job($db);
		$job2->label = 'Commercial terrain';
		$job2->description = 'Prospection, prise de commandes, suivi clients';
		$result = $job2->create($user);
		if ($result > 0) {
			$job2Id = $result;
			// No validate() — Dolibarr lacks mod_job_standard numbering module
		}

		$job3 = new Job($db);
		$job3->label = 'Technicien installation';
		$job3->description = 'Livraison, montage et installation de mobilier sur site client';
		$result = $job3->create($user);
		if ($result > 0) {
			$job3Id = $result;
			// No validate() — Dolibarr lacks mod_job_standard numbering module
		}

		if ($user3Id > 0 && $job1Id > 0) {
			$eval1 = new Evaluation($db);
			$eval1->label = 'Evaluation annuelle Philippe Durand';
			$eval1->fk_user = $user3Id;
			$eval1->fk_job = $job1Id;
			$eval1->date_eval = $dateM3;
			$eval1->note_private = 'Excellent travail sur le projet hotel. Maitrise parfaite des assemblages tenon-mortaise. A former les apprentis.';
			$result = $eval1->create($user);
			if ($result > 0) {
				$eval1->validate($user);
			}
		}

		if ($user1Id > 0 && $job2Id > 0) {
			$eval2 = new Evaluation($db);
			$eval2->label = 'Evaluation semestrielle Jean Dupont';
			$eval2->fk_user = $user1Id;
			$eval2->fk_job = $job2Id;
			$eval2->date_eval = $dateM1;
			$eval2->note_private = 'Bons resultats commerciaux. CA en hausse de 15%. Ameliorer le suivi des relances prospects.';
			$result = $eval2->create($user);
			if ($result > 0) {
				$eval2->validate($user);
			}
		}

		if ($user2Id > 0 && $job3Id > 0) {
			$eval3 = new Evaluation($db);
			$eval3->label = 'Evaluation annuelle Marie Martin';
			$eval3->fk_user = $user2Id;
			$eval3->fk_job = $job3Id;
			$eval3->date_eval = $dateM6;
			$eval3->note_private = 'Tres bonne technicienne. Retours clients excellents. Proposee pour formation ebenisterie avancee.';
			$result = $eval3->create($user);
		}
	}

	// ========================================================================
	// Phase 27: Knowledge Management
	// ========================================================================
	if (isModEnabled('knowledgemanagement')) {
		_demoLog("Phase 27: Knowledge Management");
		require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/class/knowledgerecord.class.php';

		$kbArticles = array(
			array(
				'question' => 'Comment entretenir un meuble en chene massif ?',
				'answer' => '<p>Le chene massif necessite un entretien regulier pour conserver sa beaute :</p><ul><li>Depoussierer avec un chiffon doux et sec une fois par semaine</li><li>Appliquer une huile de protection (huile danoise ou huile dure) tous les 6 mois</li><li>Eviter les produits chimiques agressifs et l\'eau stagnante</li><li>En cas de tache, nettoyer immediatement avec un chiffon humide et secher</li><li>Proteger de la lumiere directe du soleil pour eviter le jaunissement</li></ul>',
			),
			array(
				'question' => 'Quels sont les delais de fabrication standard ?',
				'answer' => '<p>Les delais de fabrication varient selon le type de meuble :</p><ul><li><strong>Table standard</strong> : 3 a 4 semaines</li><li><strong>Chaise</strong> : 2 semaines</li><li><strong>Etagere</strong> : 2 a 3 semaines</li><li><strong>Bureau sur mesure</strong> : 4 a 6 semaines</li><li><strong>Mobilier complet (hotel, restaurant)</strong> : 8 a 12 semaines selon volume</li></ul><p>Ces delais s\'entendent apres validation du devis et reception de l\'acompte de 30%.</p>',
			),
			array(
				'question' => 'Procedure de reception des bois bruts',
				'answer' => '<p>A la reception des livraisons de bois :</p><ol><li>Verifier le bon de livraison (essences, dimensions, quantites)</li><li>Controler visuellement chaque lot : noeuds, fissures, taux d\'humidite (objectif 8-12%)</li><li>Mesurer l\'humidite avec l\'hygrometre portable (rangement atelier, etagere outils)</li><li>Photographier tout defaut et le signaler au fournisseur sous 48h</li><li>Stocker a plat dans le local bois avec espacement entre les planches</li></ol>',
			),
			array(
				'question' => 'Garantie et SAV mobilier',
				'answer' => '<p>Notre politique de garantie :</p><ul><li><strong>Structure</strong> : garantie 5 ans contre tout defaut de fabrication</li><li><strong>Finition</strong> : garantie 2 ans (vernis, huile, teinte)</li><li><strong>Tapisserie</strong> : garantie 1 an (tissu, mousse, couture)</li></ul><p>En cas de reclamation : creer un ticket dans Dolibarr, affecter a l\'equipe SAV, delai d\'intervention sous 5 jours ouvrés.</p>',
			),
		);

		foreach ($kbArticles as $art) {
			$kb = new KnowledgeRecord($db);
			$kb->question = $art['question'];
			$kb->answer = $art['answer'];
			$kb->lang = !empty($conf->global->MAIN_LANG_DEFAULT) ? $conf->global->MAIN_LANG_DEFAULT : 'fr_FR';
			$result = $kb->create($user);
			if ($result > 0) {
				$kb->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 28: Partnership
	// ========================================================================
	if (isModEnabled('partnership')) {
		_demoLog("Phase 28: Partnerships");
		require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';

		$partTypeId = _demoGetDictId($db, 'c_partnership_type', 'rowid', "code = 'DEFAULT' AND active = 1");
		if (empty($partTypeId)) {
			$partTypeId = _demoGetDictId($db, 'c_partnership_type', 'rowid', "active = 1");
		}

		if ($partTypeId > 0 && $socClient1Id > 0) {
			$part1 = new Partnership($db);
			$part1->fk_type = $partTypeId;
			$part1->fk_soc = $socClient1Id;
			$part1->date_partnership_start = $dateM8;
			$part1->date_partnership_end = $dateP6;
			$part1->note_private = 'Partenariat prescripteur avec Hotel Le Grand Bleu — commission 5% sur clients adresses';
			$result = $part1->create($user);
			if ($result > 0) {
				$part1->validate($user);
				$part1->approve($user);
			}
		}

		if ($partTypeId > 0 && $socClient4Id > 0) {
			$part2 = new Partnership($db);
			$part2->fk_type = $partTypeId;
			$part2->fk_soc = $socClient4Id;
			$part2->date_partnership_start = $dateM2;
			$part2->date_partnership_end = $dateP3;
			$part2->note_private = 'Partenariat WorkSpace Republique — mobilier de demonstration en showroom coworking';
			$result = $part2->create($user);
			if ($result > 0) {
				$part2->validate($user);
			}
		}
	}

	// ========================================================================
	// Phase 29: Supplier Proposals (Vendor Commercial Proposals)
	// ========================================================================
	if (isModEnabled('supplier_proposal')) {
		_demoLog("Phase 29: Supplier proposals");
		require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';

		if ($socFourn1Id > 0) {
			$sprop1 = new SupplierProposal($db);
			$sprop1->socid = $socFourn1Id;
			$sprop1->date = $dateM5;
			$sprop1->note_private = 'Demande de prix annuelle bois massif — negociation volume';
			$sprop1->model_pdf = $defaultPdfModel['SupplierProposal'];
			$result = $sprop1->create($user);
			if ($result > 0) {
				$sprop1->addline('Planches chene brut 200x30x3 cm — prix unitaire lot 100', 42, 100, '20.0');
				$sprop1->addline('Planches noyer brut 150x25x3 cm — prix unitaire lot 50', 52, 50, '20.0');
				$sprop1->addline('Planches hetre 180x30x3 cm — prix unitaire lot 60', 38, 60, '20.0');
				$sprop1->valid($user);
			}
		}

		if ($socFourn2Id > 0) {
			$sprop2 = new SupplierProposal($db);
			$sprop2->socid = $socFourn2Id;
			$sprop2->date = $dateM3;
			$sprop2->note_private = 'Consultation tissus pour collection 2026';
			$sprop2->model_pdf = $defaultPdfModel['SupplierProposal'];
			$result = $sprop2->create($user);
			if ($result > 0) {
				$sprop2->addline('Tissu lin naturel (rouleau 50m)', 22, 50, '20.0');
				$sprop2->addline('Tissu velours bleu canard (rouleau 30m)', 35, 30, '20.0');
				$sprop2->addline('Mousse HR 35kg/m3 5cm (plaques)', 16, 40, '20.0');
				$sprop2->valid($user);
			}
		}

		if ($socFourn4Id > 0) {
			$sprop3 = new SupplierProposal($db);
			$sprop3->socid = $socFourn4Id;
			$sprop3->date = $dateW2;
			$sprop3->note_private = 'Demande prix vernis ecolabel gamme pro';
			$sprop3->model_pdf = $defaultPdfModel['SupplierProposal'];
			$result = $sprop3->create($user);
			if ($result > 0) {
				$sprop3->addline('Vernis mat ecolabel 5L — prix pro', 32, 20, '20.0');
				$sprop3->addline('Huile protection bois exotique 2.5L', 30, 10, '20.0');
			}
		}
	}

	// ========================================================================
	// Phase 30: Receptions (linked to supplier orders)
	// ========================================================================
	if (isModEnabled('reception') && isModEnabled('supplier_order')) {
		_demoLog("Phase 30: Receptions");
		require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

		if (isset($sord1) && $sord1->id > 0 && $warehouseId > 0) {
			$sord1->fetch_lines();
			if (!empty($sord1->lines)) {
				$rec1 = new Reception($db);
				$rec1->socid = $socFourn1Id;
				$rec1->origin = 'order_supplier';
				$rec1->origin_id = $sord1->id;
				$rec1->date_reception = $dateM9;
				$rec1->date_delivery = $dateM9;
				$rec1->note_private = 'Reception complete bois massif — controle qualite OK';
				$rec1->model_pdf = $defaultPdfModel['Reception'];
				$result = $rec1->create($user);
				if ($result > 0) {
					$lineError = 0;
					foreach ($sord1->lines as $line) {
						if ($rec1->create_line($warehouseId, $line->id, $line->qty) <= 0) {
							_demoLog("WARNING: reception line failed for supplier order ".$sord1->id." (line ".$line->id.") — skipping validation");
							$lineError++;
							break;
						}
					}
					if (!$lineError) {
						$rec1->valid($user);
					}
				}
			}
		}

		if (isset($sord4) && $sord4->id > 0 && $warehouseId > 0) {
			$sord4->fetch_lines();
			if (!empty($sord4->lines)) {
				$rec2 = new Reception($db);
				$rec2->socid = $socFourn4Id;
				$rec2->origin = 'order_supplier';
				$rec2->origin_id = $sord4->id;
				$rec2->date_reception = $dateM1;
				$rec2->date_delivery = $dateM1;
				$rec2->note_private = 'Reception vernis et huiles — lot conforme';
				$rec2->model_pdf = $defaultPdfModel['Reception'];
				$result = $rec2->create($user);
				if ($result > 0) {
					$lineError = 0;
					foreach ($sord4->lines as $line) {
						if ($rec2->create_line($warehouseId, $line->id, $line->qty) <= 0) {
							_demoLog("WARNING: reception line failed for supplier order ".$sord4->id." (line ".$line->id.") — skipping validation");
							$lineError++;
							break;
						}
					}
					if (!$lineError) {
						$rec2->valid($user);
					}
				}
			}
		}
	}

	// Check critical data was created
	if ($socClient1Id <= 0) {
		_demoLog("ERROR: no third parties created");
		$error++;
	}
	if ($prodTableId <= 0 && $prodChaiseId <= 0) {
		_demoLog("ERROR: no products created");
		$error++;
	}

	if ($error) {
		_demoLog("Demo data generation finished with $error critical error(s)");
		if ($manageTransaction) {
			$db->rollback();
		}
		return -1;
	}

	if ($manageTransaction) {
		$db->commit();
	}
	_demoLog("All demo data generated successfully");
	return 0;
}


/**
 * Log a message (print in CLI, store for web)
 *
 * @param	string	$msg	Message
 * @return	void
 */
function _demoLog($msg)
{
	$sapi = php_sapi_name();
	if (substr($sapi, 0, 3) == 'cli' || substr($sapi, 0, 3) == 'cgi') {
		print $msg."\n";
	}
	dol_syslog("generate-demo: ".$msg);
}


/**
 * Get a dictionary value by condition
 *
 * @param	DoliDB	$db			Database handler
 * @param	string	$table		Table name (without prefix)
 * @param	string	$field		Field to return
 * @param	string	$where		WHERE clause
 * @return	int					Value or 0
 */
function _demoGetDictId($db, $table, $field, $where)
{
	$sql = "SELECT ".$field." FROM ".MAIN_DB_PREFIX.$table." WHERE ".$where." LIMIT 1";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			return (int) $obj->$field;
		}
		// Query succeeded but dictionary has no matching active row.
		// Callers must check the return value is > 0 before using it.
		_demoLog("WARNING: no dictionary entry in ".$table." for [".$where."] — caller should fall back");
	} else {
		_demoLog("WARNING: dictionary query failed on ".$table." — ".$db->lasterror());
	}
	return 0;
}
