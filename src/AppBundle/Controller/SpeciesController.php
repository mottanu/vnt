<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use AppBundle\Document\Species;
use AppBundle\Controller\UtilsController;
use AppBundle\Controller\GoogleCloudStorageController;
use Symfony\Component\HttpFoundation\Request;

class SpeciesController extends Controller
{
  
  /**
   * Matches /v1/species/list exactly
   *
   * @Route("/v1/species/list", name="speciesList")
   * @Method({"GET"})
   *
   * @ApiDoc(
   *     resource=true,
   *     resourceDescription="Species list.",
   *     description="Species list.",
   *     section="Species",
   *  )
   */
  public function speciesList() {
    
    $dm = $this->get('doctrine_mongodb')->getManager();
    $repository = $dm->getRepository('AppBundle:Species');
    $speciesCursor = $repository->findAll();
    $speciesList = array();
    foreach($speciesCursor as $specie) {
        $speciesList[] = $specie->display();
    }
         
    $resp = array(
      'ok' => true, 
      'result' => array(
        'species' => $speciesList
      )
    );
    return new JsonResponse($resp);
  }
  
    
}