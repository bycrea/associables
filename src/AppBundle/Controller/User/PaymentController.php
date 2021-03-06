<?php

namespace AppBundle\Controller\User;

use AppBundle\Entity\Donation;
use AppBundle\Entity\Transaction;
use Beelab\PaypalBundle\Paypal\Exception;
use Beelab\PaypalBundle\Paypal\Service;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends Controller
{

    /**
     * @Route("/payment", name="user_payment")
     *
     * Récupère les donations du panier liées à un utilisateur
     * Envoi les donations dans une transaction PayPal grâce à 'BeelabPaypalBundle'
     */
    public function paymentAction(Service $service)
    {
        // Récupère les donations en Panier, liées à l'utilisateur connecté
        $donations = $this->getDoctrine()->getRepository(Donation::class)
            ->findBy(['user' => $this->getUser(), 'paymentStatus' => Donation::PAY_BASKET]);

        // Récupère le montant total pour la transaction
        $totalAmount = $this->getDoctrine()->getRepository(Donation::class)
            ->getBasketTotal($this->getUser()->getId());

        // Verifie que le montant et la quantité soit suppérieur à zéro pour lancer la transaction
        if ($totalAmount['amount'] > 0 && $totalAmount['quantity'] > 0)
        {
            // Création d'une nouvelle entity Transaction
            $transaction = new Transaction($totalAmount['amount']);
        } else {

            // Sinon redirection vers le Panier
            $this->addFlash('danger', 'Votre panier est vide.');
            return $this->redirectToRoute('basket');
        }

        // 'setDonations' transmet les détails de la transaction à PayPal via le Bundle
        $transaction->setDonations($donations);

        try {
            // Méthodes liées au Service pour lancer la transaction et Updater la table Transaction
            $response = $service->setTransaction($transaction)->start();
            $this->getDoctrine()->getManager()->persist($transaction);
            $this->getDoctrine()->getManager()->flush();

            // Redirige vers l'url de PayPal généré par la dépendance 'Service'
            return $this->redirect($response->getRedirectUrl());

        } catch (Exception $e) {

            //throw new HttpException(503, 'Erreur de paiement', $e);
            $this->addFlash('danger', 'Une erreur s\'est produite, veuillez nous excuser.');
            return $this->redirectToRoute('basket');
        }
    }


    /**
     * @Route("/payment_cancel", name="user_payment_cancel")
     *
     * Vérifie la validité du token retourné par PayPal
     * (correspondance avec le token de la transaction envoyé)
     * Update la table Transaction et Redirige vers le panier
     */
    public function canceledPaymentAction(Request $request)
    {
        $token = $request->query->get('token');
        $transaction = $this->getDoctrine()->getRepository(Transaction::class)->findOneByToken($token);

        if (null === $transaction) {
            throw $this->createNotFoundException(sprintf('Transaction with token %s not found.', $token));
        }

        $transaction->cancel(null);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('basket');
    }


    /**
     * @Route("/payment_success", name="user_payment_success")
     *
     * Modifie les 'paymentStatus' et 'paymentMode' des donations en cas de success ou d'erreur
     * Redirige vers le dashboard utilisateur
     */
    public function completedPaymentAction(Service $service, Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        // Vérifi que le token reçu via PayPal corresponde bien à la transaction envoyé
        $token = $request->query->get('token');
        $transaction = $this->getDoctrine()->getRepository(Transaction::class)->findOneByToken($token);

        if (null === $transaction) {
            throw $this->createNotFoundException(sprintf('Transaction with token %s not found.', $token));
        }

        // Récupère les donations liées à l'utilisateur en Panier
        $donations = $this->getDoctrine()->getRepository(Donation::class)
            ->findBy(['user' => $this->getUser(), 'paymentStatus' => Donation::PAY_BASKET]);

        // Transmettre les détails de la transaction pour getItems
        $transaction->setDonations($donations);

        // Inscrit la transaction comme terminé en base de donnée
        $service->setTransaction($transaction)->complete();
        $entityManager->flush();

        // Si la transcation est défini comme différent de 'isOk' (soit erronée)
        if (!$transaction->isOk())
        {
            // On selectionne le status refusé (paymentStatus = 2)
            $status = Donation::PAY_REFUSED;
            // On averti l'utilisateur
            $this->addFlash('danger', 'Une erreur est survenue, veuillez contacter le service PayPal.');

        } else {
            // Sinon, selectionne le status réussi (soit en attente de transfert : paymentStatus = 4)
            $status = Donation::PAY_IN_TRANSFER;
            // On averti l'utilisateur
            $this->addFlash('success', 'Paiement validé, merci pour vos dons et votre confiance.');
        }

        foreach($donations as $donation)
        {
            // Pour chaque donations on inscrit définitivement le status de la transaction
            // ainsi que le mode de paiement (soit PayPal : paymentMode = 1)
            $donation->setPaymentStatus($status);
            $donation->setPaymentMode(Donation::PAY_PAYPAL);
            $entityManager->persist($donation);
        }
        $entityManager->flush();

        return $this->redirectToRoute('user_donations');
    }
}
