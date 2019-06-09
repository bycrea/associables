<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Donation;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param $filter //Colonne visé par le tri
     * @param $order //Sens du tri (ASC/DESC)
     * @return array
     *
     * Retourne L'Entity [User], Le montant total de ses Dons [amount], Le nombre total de ses Dons [nb]
     */
    public function OrderByCustom($filter, $order)
    {
        $queryBuilder = $this->createQueryBuilder('user');

        $queryBuilder
            // Sélectionne les colonnes nécéssaires (entity User/SUM des dons/COUNT des dons)
            // Là ou les dons sont égale à 'paymentStatus' = PAY_IN_TRANSFER ou PAY_PROCESSED sinon = 0
            ->select('user, 
                            CASE WHEN d.paymentStatus IN (:status) THEN SUM(d.amount) 
                                 ELSE 0 END AS amount, 
                            CASE WHEN d.paymentStatus IN (:status) THEN COUNT(d.id) 
                                 ELSE 0 END AS nb')

            ->setParameter('status', [Donation::PAY_IN_TRANSFER, Donation::PAY_PROCESSED])
            // Jointure de la l'entity Donnation AS d
            ->leftJoin('user.donations', 'd')
            // Regroupement par Utilisateur
            ->groupBy('user')
            // Trié par la colonne visé par le filtre en paramètre
            ->orderBy($filter, $order);

        // Retourne un tableau [user, amount, nb]
        return $queryBuilder->getQuery()->getResult();
    }

}
