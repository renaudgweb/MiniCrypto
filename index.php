<?php
/**
 * @file index.php
 * @author RenaudG
 * @version 0.1 Avril 2025
 *
 * Script via API Coingecko
 * 
 */

require "MiniPaviCli.php";
require "MiniCrypto.php";

//error_reporting(E_USER_NOTICE|E_USER_WARNING);
error_reporting(E_ERROR);
ini_set('display_errors',0);

try {
    MiniPavi\MiniPaviCli::start();

    // Initialisation du contexte utilisateur
    if (MiniPavi\MiniPaviCli::$fctn == 'CNX' || MiniPavi\MiniPaviCli::$fctn == 'DIRECTCNX') {
        $context = array('step' => 'accueil');
    } else {
        $context = unserialize(MiniPavi\MiniPaviCli::$context);
    }

    // Récupération des prix des cryptomonnaies
    $cryptoPrices = getPrices();

    // Initialisation des variables
    $vdt = ''; // Le contenu vidéotex à envoyer au Minitel de l'utilisateur
    $cmd = null; // La commande à exécuter au niveau de MiniPavi
    $directCall = false; // Ne pas rappeler le script immédiatement
    // Définir les paramètres régionaux en français
    //setlocale(LC_TIME, 'fr_FR.UTF-8'); // Avant PHP8.1
    $formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::SHORT);
    $formatter->setPattern('d MMMM yyyy \'à\' HH:mm');

    // Gestion de la navigation utilisateur
    switch ($context['step']) {
        case 'accueil':
            // Affichage de la page d'accueil avec uniquement le prix du Bitcoin
            $vdt = MiniPavi\MiniPaviCli::clearScreen() . PRO_MIN . PRO_LOCALECHO_OFF;
            $vdt .= file_get_contents('btc.vdt');

            // Recherche des informations sur le Bitcoin
            $bitcoinPrice = "";
            foreach ($cryptoPrices as $crypto) {
                if ($crypto['titre'] == ucfirst('bitcoin')) {
                    $bitcoinPrice = $crypto['desc'];
                    break;
                }
            }

            // Affichage du prix du Bitcoin
            //$vdt .= MiniPavi\MiniPaviCli::writeCentered(12, "\nPrix actuel du Bitcoin mis à jour le :\n" . strftime('%e %B %Y à %Hh%M') . ":\n", VDT_TXTYELLOW); // Avant PHP8.1
            $vdt .= MiniPavi\MiniPaviCli::writeCentered(12, "\nPrix actuel du Bitcoin mis à jour le :\n" . $formatter->format(new DateTime()) . " :\n", VDT_TXTYELLOW);
            $vdt .= MiniPavi\MiniPaviCli::writeCentered(12, $bitcoinPrice . "\n", VDT_TXTYELLOW);
            $vdt .= "\nAppuyez sur SUITE pour plus d'informations.";

            $context['step'] = 'affichage_prix';
            break;

        case 'affichage_prix':
            // Affichage des prix des cryptomonnaies
            $vdt = MiniPavi\MiniPaviCli::clearScreen();
            //$vdt .= "Prix des cryptomonnaies mis à jour le " . strftime('%e %B %Y à %Hh%M') . " :\n\n"; // Avant PHP8.1
            $vdt .= "Prix des cryptomonnaies mis à jour le " . $formatter->format(new DateTime()) . " :\n\n";

            foreach ($cryptoPrices as $crypto) {
                $vdt .= $crypto['titre'] . ": " . $crypto['desc'] . "\n\n";
            }

            $vdt .= "\nAppuyez sur SOMMAIRE pour revenir à l'accueil.";
            $context['step'] = 'affichage_prix';
            break;
    }

    // URL à appeler lors de la prochaine saisie utilisateur
    $nextPage = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

    // Envoi à la passerelle du contenu à afficher, de l'URL du prochain script à appeler,
    // du contexte utilisateur sérialisé, et de l'éventuelle commande à exécuter
    MiniPavi\MiniPaviCli::send($vdt, $nextPage, serialize($context), true, $cmd, $directCall);
} catch (Exception $e) {
    throw new Exception('Erreur MiniPavi: ' . $e->getMessage());
}
exit;
?>