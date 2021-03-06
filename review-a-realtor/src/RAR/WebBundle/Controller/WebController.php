<?php
namespace RAR\WebBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use RAR\AdminBundle\Entity\User;
use RAR\AdminBundle\Entity\Plan;
use RAR\WebBundle\Entity\Requests;
use RAR\AdminBundle\Modals\Login;
use RAR\WebBundle\Entity\Review;
use RAR\WebBundle\Entity\Property;
use RAR\WebBundle\Entity\Claim;
use RAR\WebBundle\Entity\Payment;
use RAR\WebBundle\Entity\PropertyImages;
use RAR\WebBundle\Entity\Subscriber;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use \DateTime;
class WebController extends Controller
{   
    /*----- Start function -- Display all states in homepage -----*/
	public function indexAction(Request $request)
    	{
	
     
    	$em = $this->getDoctrine()->getEntityManager();
   	  	$states = $em->createQueryBuilder()
      	->select('State')
      	->from('RARAdminBundle:State',  'State')
      	->getQuery()
      	->getArrayResult();    
      	
      	/*---Start Show Top Realtors here--*/
      	$type='REVIEWER';
     	$topRealtors = $em->createQueryBuilder()
      	->select('topRealtor.first_name,topRealtor.category1,topRealtor.id,topRealtor.image,topRealtor.business_name,topRealtor.business_name,topRealtor.phone,avg(review.rating) as avgRating,City.city_name, State.state_name')
      	->from('RARAdminBundle:User',  'topRealtor')
      	->innerJoin('RARWebBundle:Review', 'review',"WITH", "topRealtor.id=review.realtor_id ")
      	->leftJoin('RARAdminBundle:City',  'City', "WITH", "topRealtor.city=City.id")
		->leftJoin('RARAdminBundle:State',  'State', "WITH", "topRealtor.state=State.state_code")
		->groupBy('topRealtor.id')
      	->having('avgRating <=:rating')
      	->setMaxResults(4)
		->setParameter('rating', 5,4,3,2,1)
      	->getQuery()
      	->getArrayResult();    
      	/*---End Show Top Realtors here--*/
      	
      	/*---Start Count latestReview here--*/	
   		$Countreviewer = $em->createQueryBuilder()
      	->select('count(latestReview.id)')
      	->from('RARWebBundle:Review',  'latestReview')
      	->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=latestReview.reviewer_id ")
      	->where('latestReview.sender=:type')
      	->andwhere('latestReview.status=1')
		->setParameter('type', $type)
      	->addOrderBy('latestReview.id', 'DESC');
       	$finalCount = $Countreviewer->getQuery();
        $totalCount = $finalCount->getSingleScalarResult();
       /*---End Count latestReview here--*/
 		
 		/*---Start Pagination here--*/
 		$page = $request->get('page');			
        $count_per_page = 2;
        $total_count = $totalCount;
        $total_pages=ceil($total_count/$count_per_page);
        if(!is_numeric($page)){
            $page=1;
        }
        else{
            $page=floor($page);
        }
        if($total_count<=$count_per_page){
            $page=1;
        }
        if(($page*$count_per_page)>$total_count){
            $page=$total_pages;
        }
        $offset=0;
        if($page>1){
            $offset = $count_per_page * ($page-1);
        }
        /*---End Pagination here--*/
   		
   		/*---Start Show latestReview here--*/
   	  	$latestReviewer = $em->createQueryBuilder()
      	->select('user.first_name,user.last_name,user.business_name,latestReview.description,latestReview.rating,latestReview.reviewer_id,latestReview.headline,latestReview.creation_timestamp,latestReview.id,latestReview.id,latestReview.reviewer_id,latestReview.realtor_id,latestReview.creation_timestamp')
      	->from('RARWebBundle:Review',  'latestReview')
      	->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=latestReview.realtor_id ")
      	->where('latestReview.sender=:type')
      	->andwhere('latestReview.status=1')
	->setParameter('type', $type)
      	->addOrderBy('latestReview.id', 'DESC')
      	->setFirstResult($offset)
		->setMaxResults($count_per_page)
      	->getQuery()
      	->getArrayResult();   
      	//
		$repository = $em->getRepository('RARAdminBundle:User');
		$arrReviewer = array();
		foreach($latestReviewer as $reviewer)
		{
			$reviewId = $reviewer['id'];
			$arrReviewer[$reviewId]['first_name'] =  $reviewer['first_name'] ;
			$arrReviewer[$reviewId]['realtor_id'] =  $reviewer['realtor_id'] ;
			$arrReviewer[$reviewId]['reviewer_id'] =  $reviewer['reviewer_id'] ;
			$arrReviewer[$reviewId]['last_name'] =  $reviewer['last_name'] ;
			$arrReviewer[$reviewId]['headline'] =  $reviewer['headline'] ;
			$arrReviewer[$reviewId]['description'] =  $reviewer['description'] ;
			$arrReviewer[$reviewId]['business_name'] =  $reviewer['business_name'] ;
			$arrReviewer[$reviewId]['rating'] =  $reviewer['rating'] ;
			$arrReviewer[$reviewId]['realtor_id'] =  $reviewer['realtor_id'] ;
			$arrReviewer[$reviewId]['creation_timestamp'] =  $reviewer['creation_timestamp'] ;		
			$user = $repository->findOneBy(array('id' =>  $reviewer['reviewer_id']));
			if($user)	
				$realtorName=$user->getFirstName()." ".$user->getLastName();
			else
				$realtorName='';
			$arrReviewer[$reviewId]['realtor_name'] =  $realtorName ;	
		}
//echo "<pre>";print_r($arrReviewer);die;
		/*---End Show latestReview here--*/
      	return $this->render('RARWebBundle:Page:index.html.twig', array('states' => $states,'latestReviewer'=>$arrReviewer,'topRealtors'=>$topRealtors,'total_pages'=>$total_pages,'current_page'=> $page));
    }
    /*----- End Function -- Display all states in homepage -----*/
	
	 /*----- Start Function -- Count all Realtors -----*/
	public function getTotalsRealtos() 
    	{

 		$stateCode='';
		if(isset($_REQUEST['state']))
		{
			 $stateCode=$_REQUEST['state'];
		}
		$condition = 0; 	
		$search=$this->get('request')->request->get('search');
		$address=$this->get('request')->request->get('address'); 
		$state=$this->get('request')->request->get('state');  
		$company=$this->get('request')->request->get('company');
		$rating=$this->get('request')->request->get('ratings');  	
		if( !(isset($search)) && !(isset($address)) && !(isset($company)) && !(isset($stateCode)) && !(isset($rating)) )
		{
			$realtors = '';
		}
		else
  		{
  		
			$em = $this->getDoctrine()->getEntityManager()->createQueryBuilder();  		   		
			if(isset($reviewCountMin) && isset($reviewCountMax))
			{
				$em->select('count(User.id)');
			}
			else
			{
				$em->select('count(User.id)');
			}
			$em->from('RARAdminBundle:User', 'User');
			$em->leftJoin('RARWebBundle:Review',  'Review', "WITH", "User.id=Review.realtor_id");
			$em->leftJoin('RARAdminBundle:City',  'City', "WITH", "User.city=City.id");
			$em->leftJoin('RARAdminBundle:State',  'State', "WITH", "User.state=State.state_code");
			$em->addOrderBy('User.plan_id', 'DESC');	
			$em->andwhere('User.type=2'); 
			
		  	if(isset($search) && $search != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.first_name like :realtorName');
					$em->andwhere('User.type=2');
				 
				}
				else
				{
				$em->where('User.first_name like :realtorName or User.address like :realtorAddress or User.pincode like :realtorZip or City.city_name like :realtorCity');
					$em->andwhere('User.type=2');
				}
				$em->setParameter('realtorName', '%'.$search.'%');
				$em->setParameter('realtorAddress', '%'.$search.'%');
				$em->setParameter('realtorZip', '%'.$search.'%');
				$em->setParameter('realtorCity', '%'.$search.'%');
				$condition = 1;
			}
			
			
			if(isset($stateCode) && $stateCode != '' )
		   	{
		   		if($condition == 1 )
		   		{
					$em->andwhere('User.state like :stateCode or State.state_name like :stateName');
					$em->andwhere('User.type=2');
				 
				}
				else
				{
					$em->where('User.state like :stateCode or State.state_name like :stateName');
					$em->andwhere('User.type=2');
				}
				$em->setParameter('stateCode', '%'.$stateCode.'%');
				$em->setParameter('stateName', '%'.$stateCode.'%');
				$condition = 1;
			}
			if(isset($address) && $address != '' )
		   	{
		   		if($condition == 1 )
		   		{
					$em->andwhere('User.address like :address or City.city_name like :address or User.pincode like :address');
				}
				else
				{
					$em->Where('User.address like :address or City.city_name like :address or User.pincode like :address');
				}
				$em->setParameter('address', '%'.$address.'%');
				$condition = 1;
			}
				
			if(isset($company) && $company != '' )
		   	{
		   		if($condition == 1 )
		   		{
					$em->andwhere('User.business_name like :business');
				}
				else
				{
					$em->Where('User.business_name like :business');
				}
				$em->setParameter('business', '%'.$company.'%');
				$condition = 1;
			}
			 
			if(isset($state) && $state != '' )
		   	{		
   				if($condition == 1 )
   				{
					$em->andWhere('User.state like :state or State.state_name like :state');
				}
				else
				{
					$em->Where('User.state like :state or State.state_name like :state');
				}
				$em->setParameter('state', '%'.$state.'%');
				
				$condition = 1;
			}

			if(isset($reviewCountMin) && isset($reviewCountMax) )
			{
				if($condition == 1 )
		   		{
					$em->andwhere('Review.sender like :sender');
				}
				else
				{
		  			$em->Where('Review.sender like :sender');
				}
				$em->setParameter('sender', 'REVIEWER');
		  	}
			
		  	$userCount = $em->getQuery();
        	$realtors = $userCount->getSingleScalarResult();
       		return $realtors; 
        
    	}
	}
	/*----- Start Function -- Show Write Review Page -----*/
	/*public function writeReviewAction()
    {

        return $this->redirect($this->generateUrl('rar_web_homepage'));
    }*/
    /*----- End function -- Show Write Review Page -----*/

    /*-----Start Function -- Search Realtors -----*/
	/*public function realtorsAction(Request $request)
 	{ 
 		$a= $this->getTotalsRealtos();
 		$realtors = array();
		$sortedRealtors = array();
		$notFoundMsg = '';
		$searchs=$this->get('request')->request->get('hidsorting');
		$session = $this->getRequest()->getSession(); 
		if( $session->get('countPage') && $session->get('countPage') > 0 )
        {
            if($searchs>0)
             	$session->set('countPage', $searchs); 
       	}      
        else
        {    
			$session->set('countPage', $searchs); 
		}
 		$page = $request->get('page');							
 		if( $session->get('countPage') && $session->get('countPage') > 0 )
        {
                 
            $count_per_page = $session->get('countPage');
        }
        else
        {
                    
            $count_per_page = 20;
        }
        $total_count = $a;
        $total_pages=ceil($total_count/$count_per_page);
        
        if(!is_numeric($page))
        {
            $page=1;
        }
        else
        {
            $page=floor($page);
        }
        if($total_count<=$count_per_page)
        {
            $page=1;
        }
        if(($page*$count_per_page)>$total_count)
        {
            $page=$total_pages;
        }
        $offset=0;
        if($page>1)
        {
            $offset = $count_per_page * ($page-1);
        }
 	
 		$stateCode='';

 		
		$em = $this->getDoctrine()->getEntityManager();
		$adv = $em->createQueryBuilder()
		->select('adv')
		->from('RARAdminBundle:Advertiesment',  'adv')
		->where('adv.type=:type')
		->setParameter('type',"ADV")
		->getQuery()
		->getResult();
		
		
	    if(isset($_REQUEST['state']))
	    {
			$stateCode=$_REQUEST['state'];
		}
		$condition = 0; 		
		$search=$this->get('request')->request->get('search');
		$address=$this->get('request')->request->get('address'); 
		$state=$this->get('request')->request->get('state');  
		$company=$this->get('request')->request->get('company');
		$rating=$this->get('request')->request->get('ratings');  	
		if( !(isset($search)) && !(isset($address)) && !(isset($company)) && !(isset($stateCode)) && !(isset($rating)) )
		{
			$realtors = '';
		}
		else
  		{
  		
			$em = $this->getDoctrine()->getEntityManager()->createQueryBuilder();  		   		
			if( isset($reviewCountMin) && isset($reviewCountMax) )
			{
				$em->select('User.id, User.first_name, User.last_name, User.business_name,User.category1, User.phone,User.pincode User.fax, User.image, Review.realtor_id,avg(Review.rating)) as avgRating, count(Review.rating) AS totalReviews, City.city_name, State.state_name');
			}
			else
			{
				$em->select('User.id, User.first_name, User.last_name, User.business_name, User.phone,User.pincode,User.category1, User.fax, User.image, Review.realtor_id, avg(Review.rating) as avgRating, City.city_name, State.state_name');
			}
			$em->from('RARAdminBundle:User', 'User');
			$em->leftJoin('RARWebBundle:Review',  'Review', "WITH", "User.id=Review.realtor_id");
			$em->leftJoin('RARAdminBundle:City',  'City', "WITH", "User.city=City.id");
			$em->leftJoin('RARAdminBundle:State',  'State', "WITH", "User.state=State.state_code");
			$em->andwhere('User.type=2');
			$em->andwhere('User.status=1');
			
			
			$em->addOrderBy('User.plan_id', 'DESC');	
		  	if( isset($search) && $search != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.first_name like :realtorName');
					$em->andwhere('User.type=2');
					$em->andwhere('User.status=1');
					
				 
				}
				else
				{
					$em->where('User.first_name like :realtorName or User.address like :realtorAddress or User.pincode like :realtorZip or City.city_name like :realtorCity or User.business_name like :business');
					$em->andwhere('User.type=2');
					$em->andwhere('User.status=1');
					
				}
				$em->setParameter('realtorName', '%'.$search.'%');
				$em->setParameter('business', '%'.$search.'%');
				$em->setParameter('realtorAddress', '%'.$search.'%');
				$em->setParameter('realtorZip', '%'.$search.'%');
				$em->setParameter('realtorCity', '%'.$search.'%');
				
				$condition = 1;
			}

			if( isset($stateCode) && $stateCode != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.state like :stateCode or State.state_name like :stateName');
					$em->andwhere('User.type=2');
					$em->andwhere('User.status=1');
				 
				}
				else
				{
					$em->where('User.state like :stateCode or State.state_name like :stateName');
					$em->andwhere('User.type=2');
					$em->andwhere('User.status=1');
				}
				$em->setParameter('stateCode', '%'.$stateCode.'%');
				$em->setParameter('stateName', '%'.$stateCode.'%');
				$condition = 1;
			}
			if( isset($address) && $address != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.address like :address or City.city_name like :address or User.pincode like :address');
						$em->andwhere('User.type=2');
						$em->andwhere('User.status=1');
				}
				else
				{
					$em->Where('User.address like :address or City.city_name like :address or User.pincode like :address');
						$em->andwhere('User.type=2');
						$em->andwhere('User.status=1');
				}
				$em->setParameter('address', '%'.$address.'%');
				$condition = 1;
			}
				
			if( isset($company) && $company != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.business_name like :business');
					$em->andwhere('User.type=2');
					$em->andwhere('User.status=1');						
				}
				else
				{
					$em->Where('User.business_name like :business');
				}
				$em->setParameter('business', '%'.$company.'%');
				$condition = 1;
			}
			 
			if( isset($state) && $state != '' )
		   	{		
   				if( $condition == 1 )
   				{
					$em->andWhere('User.state like :state or State.state_name like :state');
						$em->andwhere('User.type=2');
						$em->andwhere('User.status=1');
				}
				else
				{
					$em->Where('User.state like :state or State.state_name like :state');
						$em->andwhere('User.type=2');
						$em->andwhere('User.status=1');
				}
				$em->setParameter('state', '%'.$state.'%');
				
				$condition = 1;
			}

			if( isset($rating) && $rating != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andhaving('avgRating =:rating');
					$em->andwhere('User.type=2');
					$em->andwhere('Review.status=1');
					$em->andwhere('User.status=1');
				}
				else
				{
					$em->having('avgRating =:rating');
					$em->andwhere('User.type=2');
					$em->andwhere('User.status=1');
					
				}
		 		$em->setParameter('rating', $rating);
		 		$condition = 1;
			}
				
			if( isset($reviewCountMin) && isset($reviewCountMax) )
			{
				if( $condition == 1 )
		   		{
					$em->andwhere('Review.sender like :sender');
						$em->andwhere('User.type=2');
				}
				else
				{
		  			$em->Where('Review.sender like :sender');
		  				$em->andwhere('User.type=2');
				}
				$em->setParameter('sender', 'REVIEWER');
		  	}
			
		  	$em->setFirstResult($offset);
		    $em->setMaxResults($count_per_page);
		    //echo $em->getQuery()->getSQL();die;
		  	$realtors = $em->getQuery()->getArrayResult();  	
		  	$filteredRealtors = array();
		  	foreach($realtors as $realtor)
		  	{
		  		if( isset($reviewCountMin) && isset($reviewCountMax) )
		  		{
		  			if( $realtor['totalReviews'] >= $reviewCountMin && $realtor['totalReviews'] <= $reviewCountMax )
		  			{
		  				$filteredRealtors[] = $realtor;
		  			}
		  		}
		  	}

		  	if( is_array($filteredRealtors) && count($filteredRealtors) > 0 )
		  		$realtors = $filteredRealtors;
		  		$totalRealtors = count($realtors);
			
			if(isset($realtors)&& is_array($realtors)&&count($realtors)>0)
			{
				foreach($realtors as $realtor)
				{
						
					$queryReview = $this->getDoctrine()->getEntityManager()->createQueryBuilder();
					$queryReview->select('Review1.realtor_id, avg(Review1.rating) as avgRating');
					$queryReview->from('RARWebBundle:Review', 'Review1');
					$queryReview->groupBy('Review1.realtor_id');
					$queryReview->where('Review1.realtor_id= :realtorId');
					$queryReview->where('Review1.status= :realtorId');
					$queryReview->setParameter('realtorId', $realtor['id']);
					$reviews = $queryReview->getQuery()->getArrayResult(); 
					if( isset($reviews)  && is_array($reviews) && count($reviews) > 0 )
					{
						$review['avgRating'] = $reviews[0]['avgRating'];
					}
					else
					{
						$review['avgRating'] = 0;
 					}
					$sortedRealtors[] = array_merge($realtor, $review);
				}
			}
			else
			{
					$notFoundMsg = 'No Realtor Found';
			}	
	  		$rating = array();
			foreach ($sortedRealtors as $key => $row)
			{
			    $rating[$key] = $row['avgRating'];
			}
		    array_multisort($rating, SORT_DESC, $sortedRealtors);
		}
        return $this->render('RARWebBundle:Page:realtors.html.twig',array('notFoundMsg'=>$notFoundMsg,'search'=>$search,'address'=>$address,'realtors'=>$realtors,'totalRealtors'=> $totalRealtors,'adv'=>$adv,'stateCode'=>$stateCode,'total_pages'=>$total_pages,'current_page'=> $page,'searchs'=>$searchs,'a'=>$a));
	}
*/
	 /*----- End function -- Search Realtors -----*/
	public function realtorsAction(Request $request)
 	{ 
 		$a= $this->getTotalsRealtos();
 		$realtors = array();
		$sortedRealtors = array();
		$notFoundMsg = '';
		$searchs=$this->get('request')->request->get('hidsorting');
		$session = $this->getRequest()->getSession(); 
		if( $session->get('countPage') && $session->get('countPage') > 0 )
       		 {
            		if($searchs>0)
             		$session->set('countPage', $searchs); 
       		 }      
        else
        {    
			$session->set('countPage', $searchs); 
		}
 		$page = $request->get('page');							
 		if( $session->get('countPage') && $session->get('countPage') > 0 )
        {
                 
            $count_per_page = $session->get('countPage');
        }
        else
        {
                    
            $count_per_page = 20;
        }
        $total_count = $a;
        $total_pages=ceil($total_count/$count_per_page);
        
        if(!is_numeric($page))
        {
            $page=1;
        }
        else
        {
            $page=floor($page);
        }
        if($total_count<=$count_per_page)
        {
            $page=1;
        }
        if(($page*$count_per_page)>$total_count)
        {
            $page=$total_pages;
        }
        $offset=0;
        if($page>1)
        {
            $offset = $count_per_page * ($page-1);
        }
 	
 		$stateCode='';

 		/*---- Start Code for Show Advertiesment -----*/
		$em = $this->getDoctrine()->getEntityManager();
		$adv = $em->createQueryBuilder()
		->select('adv')
		->from('RARAdminBundle:Advertiesment',  'adv')
		->where('adv.type=:type')
		->setParameter('type',"ADV")
		->getQuery()
		->getResult();
		/*---- End Code for Show Advertiesment -----*/
		
	    if(isset($_REQUEST['state']))
	    {
			$stateCode=$_REQUEST['state'];
		}
		$condition = 0; 		
		$search=$this->get('request')->request->get('search');

		$address=$this->get('request')->request->get('address'); 
		$state=$this->get('request')->request->get('state');  
		$company=$this->get('request')->request->get('company');
//echo $company;die;
		$rating=$this->get('request')->request->get('ratings');  	
		if( !(isset($search)) && !(isset($address)) && !(isset($company)) && !(isset($stateCode)) && !(isset($rating)) )
		{
			$realtors = '';
		}
		else
  		{
  		
			$em = $this->getDoctrine()->getEntityManager()->createQueryBuilder();  		   		
			if( isset($reviewCountMin) && isset($reviewCountMax) )
			{
				$em->select('User.id, User.first_name, User.last_name, User.business_name,User.category1, User.phone,User.pincode User.fax, User.image, Review.realtor_id, Review.rating as avgRating, count(Review.rating) AS totalReviews, City.city_name, State.state_name');
			}
			else
			{
				$em->select('User.id, User.first_name, User.last_name, User.business_name, User.phone,User.pincode,User.category1, User.fax, User.image, Review.realtor_id,  Review.rating as avgRating, City.city_name, State.state_name');
			}
			$em->from('RARAdminBundle:User', 'User');
			$em->leftJoin('RARWebBundle:Review',  'Review', "WITH", "User.id=Review.realtor_id");
			$em->leftJoin('RARAdminBundle:City',  'City', "WITH", "User.city=City.id");
			$em->leftJoin('RARAdminBundle:State',  'State', "WITH", "User.state=State.state_code");
			$em->andwhere('User.type=2');
			
			
			$em->addOrderBy('User.plan_id', 'DESC');	
		  	if( isset($search) && $search != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.first_name like :realtorName');
					$em->andwhere('User.type=2');
					//$em->andwhere('User.id !=:userId');
				 
				}
				else
				{
					$em->where('User.first_name like :realtorName or User.address like :realtorAddress or User.pincode like :realtorZip or City.city_name like :realtorCity or User.business_name like :business');
					$em->andwhere('User.type=2');
					//$em->andwhere('User.id !=:userId');
				}
				$em->setParameter('realtorName', '%'.$search.'%');
				$em->setParameter('business', '%'.$search.'%');
				$em->setParameter('realtorAddress', '%'.$search.'%');
				$em->setParameter('realtorZip', '%'.$search.'%');
				$em->setParameter('realtorCity', '%'.$search.'%');
				//$em->setParameter('userId', $session->get('userId'));
				$condition = 1;
			}

			if( isset($stateCode) && $stateCode != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.state like :stateCode or State.state_name like :stateName');
					$em->andwhere('User.type=2');
				 
				}
				else
				{
					$em->where('User.state like :stateCode or State.state_name like :stateName');
					$em->andwhere('User.type=2');
				}
				$em->setParameter('stateCode', '%'.$stateCode.'%');
				$em->setParameter('stateName', '%'.$stateCode.'%');
				$condition = 1;
			}
			if( isset($address) && $address != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.address like :address or City.city_name like :address or User.pincode like :address');
						$em->andwhere('User.type=2');
				}
				else
				{
					$em->Where('User.address like :address or City.city_name like :address or User.pincode like :address');
						$em->andwhere('User.type=2');
				}
				$em->setParameter('address', '%'.$address.'%');
				$condition = 1;
			}
				
			if( isset($company) && $company != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andwhere('User.business_name like :business');
						$em->andwhere('User.type=2');
				}
				else
				{
					$em->Where('User.business_name like :business');
				}
				$em->setParameter('business', '%'.$company.'%');
				$condition = 1;
			}
			 
			if( isset($state) && $state != '' )
		   	{		
   				if( $condition == 1 )
   				{
					$em->andWhere('User.state like :state or State.state_name like :state');
						$em->andwhere('User.type=2');
				}
				else
				{
					$em->Where('User.state like :state or State.state_name like :state');
						$em->andwhere('User.type=2');
				}
				$em->setParameter('state', '%'.$state.'%');
				
				$condition = 1;
			}

			if( isset($rating) && $rating != '' )
		   	{
		   		if( $condition == 1 )
		   		{
					$em->andhaving('avgRating =:rating');
					$em->andwhere('User.type=2');
					$em->andwhere('Review.status=1');
				}
				else
				{
					$em->having('avgRating =:rating');
					$em->andwhere('User.type=2');
				}
		 		$em->setParameter('rating', $rating);
		 		$condition = 1;
			}
				
			if( isset($reviewCountMin) && isset($reviewCountMax) )
			{
				if( $condition == 1 )
		   		{
					$em->andwhere('Review.sender like :sender');
						$em->andwhere('User.type=2');
				}
				else
				{
		  			$em->Where('Review.sender like :sender');
		  				$em->andwhere('User.type=2');
				}
				$em->setParameter('sender', 'REVIEWER');
		  	}
			
		  	$em->setFirstResult($offset);
		    $em->setMaxResults($count_per_page);



		    //echo $em->getQuery()->getSQL();die;
		  	$realtorss = $em->getQuery()->getArrayResult();  
/*---Pagination Bundle-----*/ 
			 $paginator  = $this->get('knp_paginator');
    			$realtors = $paginator->paginate(
       			 $realtorss,
        			$request->query->get('page', 1),
        				10
    			);
/*---Pagination Bundle-----*/ 
	
		  	$filteredRealtors = array();
		  	foreach($realtors as $realtor)
		  	{
		  		if( isset($reviewCountMin) && isset($reviewCountMax) )
		  		{
		  			if( $realtor['totalReviews'] >= $reviewCountMin && $realtor['totalReviews'] <= $reviewCountMax )
		  			{
		  				$filteredRealtors[] = $realtor;
		  			}
		  		}
		  	}

		  	if( is_array($filteredRealtors) && count($filteredRealtors) > 0 )
		  		$realtors = $filteredRealtors;
		  		$totalRealtors = count($realtors);
			
			if(isset($realtors)&& is_array($realtors)&&count($realtors)>0)
			{
				foreach($realtors as $realtor)
				{
						
					$queryReview = $this->getDoctrine()->getEntityManager()->createQueryBuilder();
					$queryReview->select('Review1.realtor_id, avg(Review1.rating) as avgRating');
					$queryReview->from('RARWebBundle:Review', 'Review1');
					$queryReview->groupBy('Review1.realtor_id');
					$queryReview->where('Review1.realtor_id= :realtorId');
					$queryReview->setParameter('realtorId', $realtor['id']);
					$reviews = $queryReview->getQuery()->getArrayResult(); 
					if( isset($reviews)  && is_array($reviews) && count($reviews) > 0 )
					{
						$review['avgRating'] = $reviews[0]['avgRating'];
					}
					else
					{
						$review['avgRating'] = 0;
 					}
					$sortedRealtors[] = array_merge($realtor, $review);
				}
			}
			else
			{
					$notFoundMsg = 'No Realtor Found';
			}	
	  		$rating = array();
			foreach ($sortedRealtors as $key => $row)
			{
			    $rating[$key] = $row['avgRating'];
			}
		    array_multisort($rating, SORT_DESC, $sortedRealtors);
		}
        return $this->render('RARWebBundle:Page:realtors.html.twig',array('notFoundMsg'=>$notFoundMsg,'search'=>$search,'address'=>$address,'realtors'=>$realtors,'totalRealtors'=> $totalRealtors,'adv'=>$adv,'stateCode'=>$stateCode,'total_pages'=>$total_pages,'current_page'=> $page,'searchs'=>$searchs,'a'=>$a));
	}





	/*--Start Function--Registration of  Realtors--*/
 	public function registrationAction(Request $request)
    {
		$gbl_email_support = $this->container->getParameter('gbl_email_support');
		$website_url = $this->container->getParameter('website_url');
		$reg_url = $this->container->getParameter('reg_url');
    		$plans = $this->getPlanAction();
		// get all states 
		$states = $this->getStatesAction();
	    //get all cities
		if ($request->getMethod() == 'POST') 
    	{
     		$planId=$this->get('request')->request->get('planid');
			$planSubscription=$this->get('request')->request->get('hidPlanSubscription');
			$planCharges=$this->get('request')->request->get('hidPlanCharges');
   			$em = $this->getDoctrine()
		    ->getEntityManager();
			$plan = $em->createQueryBuilder() 
			->select('Plan')
			->from('RARAdminBundle:Plan',  'Plan')
			->where('Plan.id = :planId')
			->setParameter('planId', $planId)
			->getQuery()
			->getArrayResult(); 
			$name= $plan[0]['name'];
			$chargesH= $plan[0]['charges_half_yearly'];
			$chargesY= $plan[0]['charges_yearly'];
			$chargesM= $plan[0]['charges_monthly'];
			$planName = $name;
			if($planId==2)
			{
			$planType=$this->get('request')->request->get('planS');
			if($planType=='M')
			{
				$subscriptionType="Monthly";
				$planCharges = $chargesM;
			}
			if($planType=='H')
			{
				$subscriptionType="Half-Yearly";
				$planCharges = $chargesH;
			}
			if($planType=='Y')
			{
				$subscriptionType="Yearly";
				$planCharges = $chargesY;	
			}
	
			$file = $_FILES['file']['name'];
   			$file1  = $_FILES['file']['tmp_name'];  
    		move_uploaded_file($_FILES["file"]["tmp_name"],
      		"uploads/" . $_FILES["file"]["name"]);
      		
      		$logo = $_FILES['logo']['name'];
   			$logo1  = $_FILES['logo']['tmp_name'];  
    		move_uploaded_file($_FILES["logo"]["tmp_name"],
      		"logo/" . $_FILES["logo"]["name"]);
			$firstname=$this->get('request')->request->get('firstname');
			$lastname=$this->get('request')->request->get('lastname');
			$password=$this->get('request')->request->get('password');
			$email=$this->get('request')->request->get('email');
			$phone=$this->get('request')->request->get('phone');
			$state=$this->get('request')->request->get('state');
			$address=$this->get('request')->request->get('address');
			$address2=$this->get('request')->request->get('address2');
			$city=$this->get('request')->request->get('city');
			$fax=$this->get('request')->request->get('fax');
			$business=$this->get('request')->request->get('business');
			$overview=$this->get('request')->request->get('overview');
			$twitter=$this->get('request')->request->get('twitter');
			$facebook=$this->get('request')->request->get('facebook');
			$google=$this->get('request')->request->get('google');
			$linkedin=$this->get('request')->request->get('linkedin');
			$video=$this->get('request')->request->get('video');
			//echo'<pre>';print_r($fax);die();
			$id=$this->get('request')->request->get('id');
			$planid=$this->get('request')->request->get('planid');
			$country='US'; // set country USA
			$type=2;
			$status=2;    	
			$pincode=$this->get('request')->request->get('pincode');
			$subscriptionId=$this->get('request')->request->get('planS');
			
    		$session = $this->getRequest()->getSession();           
			$session->set('firstname', $firstname); 
			$session->set('lastname', $lastname); 
			$session->set('email', $email); 
			$session->set('password', $password); 
			$session->set('phone', $phone); 
			$session->set('state', $state); 
			$session->set('address', $address); 
			$session->set('address2', $address2); 
			$session->set('city', $city); 
			$session->set('fax', $fax); 
			$session->set('business', $business); 
			$session->set('overview', $overview); 
			$session->set('twitter', $twitter); 
			$session->set('facebook', $facebook); 
			$session->set('google', $google); 
			$session->set('linkedin', $linkedin); 
			$session->set('video', $video); 
			$session->set('id', $id); 
			$session->set('planid', $planid); 
			$session->set('pincode', $pincode); 
			$session->set('file', $file); 
			$session->set('logo', $logo); 
			$session->set('subscriptionId', $subscriptionId); 
	 		return $this->render('RARWebBundle:Page:planRegistration.html.twig',array('planName'=> $planName,'subscriptionType'=>$subscriptionType,'planCharges'=>$planCharges,'reg_url'=>$reg_url));	
		}
    	 	
    	 	$file = $_FILES['file']['name'];
   			$file1  = $_FILES['file']['tmp_name'];  
    		move_uploaded_file($_FILES["file"]["tmp_name"],
      		"uploads/" . $_FILES["file"]["name"]);
      		
      		$logo = $_FILES['logo']['name'];
   			$logo1  = $_FILES['logo']['tmp_name'];  
    		move_uploaded_file($_FILES["logo"]["tmp_name"],
      		"logo/" . $_FILES["logo"]["name"]);
			$firstname=$this->get('request')->request->get('firstname');
			$lastname=$this->get('request')->request->get('lastname');
			$password=$this->get('request')->request->get('password');
			$email=$this->get('request')->request->get('email');
			$phone=$this->get('request')->request->get('phone');
			$state=$this->get('request')->request->get('state');
			$address=$this->get('request')->request->get('address');
			$address2=$this->get('request')->request->get('address2');
			$city=$this->get('request')->request->get('city');
			$fax=$this->get('request')->request->get('fax');
			$business=$this->get('request')->request->get('business');
			$overview=$this->get('request')->request->get('overview');
			$twitter=$this->get('request')->request->get('twitter');
			$facebook=$this->get('request')->request->get('facebook');
			$google=$this->get('request')->request->get('google');
			$linkedin=$this->get('request')->request->get('linkedin');
			$video=$this->get('request')->request->get('video');
			$id=$this->get('request')->request->get('id');
			$planid=$this->get('request')->request->get('planid');
		
			$country='US'; // set country USA
			$type=2;
			$status=2;    	
			$pincode=$this->get('request')->request->get('pincode');
			$realtor=new User();
			$realtor->setFirstName($firstname);
			$realtor->setLastName($lastname);
			$realtor->setPassword(md5($password));
			$realtor->setEmail($email);
			$realtor->setPhone($phone);
			$realtor->setState($state);
			$realtor->setAddress($address);
			$realtor->setAddress2($address2);
			$realtor->setCity($city);
			$realtor->setCountry($country);
			$realtor->setType($type);
			$realtor->setStatus($status);
			$realtor->setPinCode($pincode);
			$realtor->setFax($fax);
			$realtor->setBusinessName($business);
			$realtor->setOverview($overview);
			$realtor->setPlanId($planid);
			if(isset($logo) && $logo!="")
			{
				$realtor->setLogo($logo);
			}
			else
			{
				$realtor->setLogo('company.jpeg');
			}
			$realtor->setTwitter($twitter);
			$realtor->setFacebook($facebook);
			$realtor->setGoogle($google);
			$realtor->setLinkedin($linkedin);
			$realtor->setVideo($video);
			if(isset($file) && $file!="")
			{
				$realtor->setImage($file);
			}
			else
			{
				$realtor->setImage('default_user_image.jpeg');
			}
			
			$em->persist($realtor);
			$em->flush(); 
			$reviewerId=$realtor->getId();
			
			$confirmationLink= $website_url."/confirmed/registration/".$reviewerId;//registration link
			/*--start mail function for new account--*/
			/*$date=date("Y/m/d.");
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= 'From: <support@review-a-realtor.com>' . "\r\n";
			$to = $email;
			$subject = "New Account Information";
			$txt='Hello '. $firstname.' '. $lastname.',<br><br>Your account has been created with review-a-realtor.com on '.$date.'<br><br>Your Account Details are as under: <br>Email Id: <b>'.	$email.'</b><br>Password: <b>'.	$password.'</b>';
			mail($to,$subject,$txt,$headers); //send mail  */

			$message = \Swift_Message::newInstance()
            		->setSubject('Registration')
            		->setFrom($gbl_email_support)
            		->setTo($email)
            		->setBody($this->renderView('RARWebBundle:Email:registration.txt.twig', array('firstname'=>$firstname,'lastname'=>$lastname,'password'=>$password,'email'=>$email,'confirmationLink'=>$confirmationLink)));
        $this->get('mailer')->send($message);



  

			return $this->render('RARWebBundle:Page:registerus.html.twig',array('states' => $states));		
		}
	  		return $this->render('RARWebBundle:Page:registration.html.twig',array('states' => $states,'plans'=>$plans));            
	}
   	/*--End Function--Registration of  Realtors--*/

  	/*---- Start Function - Fetch all states  -----*/ 
	public function getStatesAction()
	{
		$em = $this->getDoctrine()
		->getEntityManager();
		$states = $em->createQueryBuilder()
		->select('State')
		->from('RARAdminBundle:State',  'State')
		->getQuery()
		->getResult();
		return $states;          

	}
	/*---- End Function - Fetch all states  -----*/
  	
  	/*---- Start Function - Fetch all cities  -----*/ 
	public function getCitiesAction(Request $request)
	{   
		if( isset($_POST['stateCode']) && $_POST['stateCode'] != ''  ) 
		{
      		$em = $this->getDoctrine()
     	    ->getEntityManager();
      		$cities = $em->createQueryBuilder()
			->select('tblCity')
			->from('RARAdminBundle:City',  'tblCity')
			->where('tblCity.state_code=:stateCode')
			->setParameter('stateCode', $_POST['stateCode'])
			->getQuery()
			->getResult();
			//display all cities  
      		$html = '';
			foreach($cities as $city)
			{
				$html.='<option value="'.$city->id.'">'.$city->city_name.'</option>';
			}
			return new response($html);
		} 
		else
		{
      		$em = $this->getDoctrine()->getEntityManager();
      		$cities = $em->createQueryBuilder()
			->select('City')
			->from('RARAdminBundle:City',  'City')
			->getQuery()
			->getResult();                   
			return $cities;   
		}
	}
	/*---- End Function - Fetch all cities  -----*/ 
	
	/*---- Start Function - Fetching all plans  -----*/	
	public function getPlanAction()
	{
		$em = $this->getDoctrine()
		->getEntityManager();
		$plans = $em->createQueryBuilder()
		->select('Plan')
		->from('RARAdminBundle:Plan',  'Plan')
		->getQuery()
		->getResult();
		return $plans;          

	}
	/*---- End Function - Fetching all plans  -----*/	
	
    /*---- Start Function -- Show full profile of Realtor -----*/	
  	public function showProfileAction(Request $request,$id)
 	{

//echo $id;die;
		$expId=explode('-',$id);
		$id= $expId[0];
		$fullUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$em = $this->getDoctrine()->getEntityManager();
        $realtors = $em->getRepository('RARAdminBundle:User')->find($id);
        if (!$realtors) 
        {
            throw $this->createNotFoundException('Unable to find  realtor.');
        }
       $stateRealtor = $em->createQueryBuilder()
		->select('realtor.state,realtor.city,state.state_code,city.city_name')
		->from('RARAdminBundle:User',  'realtor')
		->leftJoin('RARAdminBundle:State', 'state', "WITH", "realtor.state=state.state_code")
		->leftJoin('RARAdminBundle:City', 'city', "WITH", "realtor.city=city.id")
		->where('realtor.id=:id')	
		->setParameter('id',$id)
		->getQuery()
		->getArrayResult();   
      $realtorCity=$stateRealtor[0]['city_name'];
        
        //echo"<pre>";print_r($realtorCity);die;
        //echo $id;die;
		$property = $em->createQueryBuilder()
		->select('Property.id,Property.name,Property.description,Property.phone,Property.address,Property.zip,Property.price,images.image_url')
		->from('RARWebBundle:Property',  'Property')
		->leftJoin('RARWebBundle:PropertyImages', 'images', "WITH", "Property.id=images.property_id")
		->where('Property.user_id=:id')	
		->andwhere('images.is_main = :mainImg')
		->setParameter('mainImg',1)
		->setParameter('id',$id)
		->getQuery()
		->getArrayResult(); 
//echo"<pre>";print_r($property);die;  
		$senderType='REVIEWER';
	
     	$reviewProcess = $em->createQueryBuilder()
        ->select("rev.sender,rev.id,rev.reviewer_id,rev.parent_id,rev.realtor_id,user.first_name,user.plan_id,user.last_name,rev.description,rev.rating,rev.creation_timestamp")
		->from("RARWebBundle:Review", "rev")
		->leftJoin('RARAdminBundle:User', 'user', "WITH", "user.id=rev.reviewer_id")
		->where('rev.realtor_id = :realtodId')
		->andwhere('rev.status = 1')
		->andWhere('rev.sender = :senderType')
		->setParameter('senderType', $senderType)
		->setParameter('realtodId', $id)
		->getQuery()
		->getArrayResult(); 	
		$senderType='REVIEWER';
	
     	$reviewOther = $em->createQueryBuilder()
        ->select("avg(rev.honesty) as avgHonesty,avg(rev.responsiveness) as avgResponsiveness,avg(rev.market_knowldege) as avgMarket")
		->from("RARWebBundle:Review", "rev")
		->leftJoin('RARAdminBundle:User', 'user', "WITH", "user.id=rev.reviewer_id")
		->where('rev.realtor_id = :realtodId')
		->andwhere('rev.status = 1')
		->andWhere('rev.sender = :senderType')
		->setParameter('senderType', $senderType)
		->setParameter('realtodId', $id)
		->getQuery()
		->getArrayResult(); 	
	
     	$reviewCount = $em->createQueryBuilder()
        ->select("count(rev.id) as reviewsCount,rev.rating")
		->from("RARWebBundle:Review", "rev")
		->leftJoin('RARAdminBundle:User', 'user', "WITH", "user.id=rev.reviewer_id")
		->where('rev.realtor_id = :realtodId')
		->andwhere('rev.status = 1')
		->andWhere('rev.sender = :senderType')
		->setParameter('senderType', $senderType)
		->setParameter('realtodId', $id)
		->groupBy('rev.rating')
		->getQuery()
		->getArrayResult();
			
		$arr = array();
		for( $i=1; $i<=5; $i++)
		{
			foreach($reviewCount as $rc)
			{
						
				if(isset($rc['rating'])&& $rc['rating'] == $i)
				{
					$arr[$i] = $rc['reviewsCount'];
						
				}
						 
			}
			if(isset($arr[$i]))
			{
					
			}
			else
			{
				$arr[$i] = 0;
			}
		} 		
		$arrPropertyId = array();
		$arrProperty = array();
		foreach($property as $propertyDetail)
		{
			if( !in_array($propertyDetail["id"], $arrPropertyId) )
			{
				$arrProperty[] = $propertyDetail;
				$arrPropertyId[] = $propertyDetail["id"];
			}
		}

		/*code for map starts here */
		$ads = $realtors->address;
     	$ad= $realtorCity;
    	$latitude = '';
		$longitude = '';
		$iframe_width = '400px';
		$iframe_height = '481px';
		$address = $ad;
		$address = urlencode($address);
		$key="AIzaSyCrLkDN07F73NgDl0F7j5bxW7D_7f2wD-s";
		$url = "http://maps.google.com/maps/geo?q=".$address."&output=json&key=".$key;
		$ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); // Comment out the line below if you receive an error on certain hosts that have security restrictions
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		$geo_json = json_decode($data, true);
		if ($geo_json['Status']['code'] == '200') 
		{
		$latitude = $geo_json['Placemark'][0]['Point']['coordinates'][0];
		$longitude = $geo_json['Placemark'][0]['Point']['coordinates'][1]; 
	   	}
	   	/*code for map end here */
		$em = $this->getDoctrine()->getEntityManager();
     	$review = $em->createQueryBuilder()
     	->select("avg(rev.rating) as avgRating")
     	->from("RARWebBundle:Review", "rev")
		->where('rev.realtor_id = :realtodId')
		->andwhere('rev.status = 1')
		->setParameter('realtodId', $id)
		->getQuery()
		->getArrayResult(); 
        return $this->render('RARWebBundle:Page:profile.html.twig', array(
            'realtors'      => $realtors,'property'=>$arrProperty,'review'=>$review,'latitude'=>$latitude,'longitude'=>$longitude,'iframe_width'=>$iframe_width,'iframe_height'=>$iframe_height,'address'=>$address,'key'=>$key,'url'=>$url,'ch'=>$ch,'reviewProcess'=>$reviewProcess,'reviewOther'=>$reviewOther,'rc'=>$arr,'ads'=>$ads,'fullUrl'=>$fullUrl
        ));
    }
   	 /*---- End Function -- Show full profile of Realtor -----*/


   	/*---- Start Function -- Show Reviews to Realtors -----*/		
 	public function showReviewsAction($id)
 	{
 		$em = $this->getDoctrine()->getEntityManager();
        /*$realtors = $em->getRepository('RARAdminBundle:User')->find($id);
        if (!$realtors) 
        {
            throw $this->createNotFoundException('Unable to findz  realtor.');
        }*/
		$expId=explode('-',$id);
		$id= $expId[0];
        	$type='REVIEWER';
		// SHOW MOST RECENT REVIEWS		     
	 		$em = $this->getDoctrine()
			->getEntityManager();
			$reviews = $em->createQueryBuilder()
			->select('Review.description,user.first_name,user.id,user.last_name,user.business_name,Review.realtor_id,Review.creation_timestamp,Review.reviewer_id,Review.rating,Review.id,Review.headline')
			->from('RARWebBundle:Review',  'Review')
			->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=Review.realtor_id")
			//->where('Review.realtor_id=:id')
			->andwhere('Review.sender=:type')
			->andwhere('Review.status=1')
			->setParameter('type', $type)			
			//->setParameter('id',$id)
			->addOrderBy('Review.id', 'DESC')
			->setMaxResults(3)
			->getQuery()
			->getResult();
		//echo"<pre>";print_r($reviews);die;
			$arrReviewer = array();
			$repository = $em->getRepository('RARAdminBundle:User');
			foreach($reviews as $reviewer)
			{
				$reviewId = $reviewer['id'];
				$arrReviewer[$reviewId]['first_name'] =  $reviewer['first_name'] ;
				$arrReviewer[$reviewId]['id'] =  $reviewer['id'] ;
				$arrReviewer[$reviewId]['headline'] =  $reviewer['headline'] ;
				$arrReviewer[$reviewId]['rating'] =  $reviewer['rating'] ;
				$arrReviewer[$reviewId]['creation_timestamp'] =  $reviewer['creation_timestamp'] ;
				$arrReviewer[$reviewId]['last_name'] =  $reviewer['last_name'] ;
				$arrReviewer[$reviewId]['description'] =  $reviewer['description'] ;
				$arrReviewer[$reviewId]['business_name'] =  $reviewer['business_name'] ;
				$arrReviewer[$reviewId]['realtor_id'] =  $reviewer['realtor_id'] ;
				$arrReviewer[$reviewId]['reviewer_id'] =  $reviewer['reviewer_id'] ;
				
				$user = $repository->findOneBy(array('id' =>  $reviewer['reviewer_id']));
				if($user)	
					$realtorName=$user->getFirstName()." ".$user->getLastName();
				else
					$realtorName='';
					$arrReviewer[$reviewId]['realtor_name'] =  $realtorName ;
			}
			//echo"<pre>";print_r($arrReviewer);die;
			$realtors = $em->getRepository('RARAdminBundle:User')->find($id);
       			if (!$realtors) 
        		{
            	
        		}
			if($id=='0')
 			{
  				return $this->render('RARWebBundle:Page:writeReviews.html.twig', array('reviews'=>$arrReviewer,'realtorId'=>$id
        ));
 			}

			 return $this->render('RARWebBundle:Page:writeReviews.html.twig', array(
            'realtors'      => $realtors,'reviews'=>$arrReviewer,'realtorId'=>$id
        ));
    } 
    /*---- End Function -- Show Reviews to Realtors -----*/

    /*---- Start Function -- Check Email exist in database -----*/		
    public function checkEmailExistanceAction(Request $request)
		{ 
 		$email = $_POST['email'];
 		$em = $this->getDoctrine()
        ->getEntityManager();
    	$repository = $em->getRepository('RARAdminBundle:User');
    	 $user = $repository->findOneBy(array('email' => $email));
    	if($user)
	    {
	    	$html = '';
			$html.='Email is already registered ';
			return new response($html);
	    }
	    else
	    {
	    		return new response('SUCCESS');
	    }
	}
    /*---- End Function -- Check Email exist in database -----*/  
   
    /*---- Start Function -- Capture email of Reviewer -----*/
 	public function captureEmailAction(Request $request,$id)
 	{ 	$em = $this->getDoctrine()->getEntityManager();	
		$gbl_email_support = $this->container->getParameter('gbl_email_support');
		$gbl_email_administrator = $this->container->getParameter('gbl_email_administrator');
		$reviewDateTime = new DateTime();
		$website_url = $this->container->getParameter('website_url');
		$realtorId=$this->get('request')->request->get('realtorReview');

			$stars=$this->get('request')->request->get('stars');
			$starH=$this->get('request')->request->get('starH');
			$starR=$this->get('request')->request->get('starR');
			$starM=$this->get('request')->request->get('starM');
			$starL=$this->get('request')->request->get('starL');
		   	$starG=$this->get('request')->request->get('starG');
			$starQ=$this->get('request')->request->get('starQ');
			$headline=$this->get('request')->request->get('headline');
			$writereview=$this->get('request')->request->get('writereview');
			$agent=$this->get('request')->request->get('agent');
			$receiveUpdates= $this->get('request')->request->get('receiveUpdates')=='on' ? 1 : 0;
			$recommend=$this->get('request')->request->get('recommend');
			$type=3;
			$status=1;
			$session = $this->getRequest()->getSession();
			if( $session->get('userId') && $session->get('userId') != '' )
			{
				$reviewerName = $em->createQueryBuilder()
			      	->select('user')
			      	->from('RARAdminBundle:User',  'user')
			      	->where('user.id=:id')
				->setParameter('id',$session->get('userId'))
			      	->getQuery()
			      	->getArrayResult();
 
				$firstname = $reviewerName[0]['first_name'];
				$emailReviewer = $reviewerName[0]['email'];
				$lastname = $reviewerName[0]['last_name'];
				$rating=new Review();
				$rating->setHeadline($headline);
				$rating->setDescription($writereview);
				$rating->setUseAgent($agent);
				$rating->setReceiveUpdates($receiveUpdates);
				$rating->setRecomendAgent($recommend);
				$rating->setRating($stars);
				$rating->setReviewerId($session->get('userId'));
			/*----Start Function -  Check Headline  -----*/
	
	$rating->setcreationTimestamp($reviewDateTime);
				if($realtorId)
				{
					$rating->setRealtorId($realtorId);
				}
				else
				{
					$rating->setRealtorId($id);
				}
				$rating->setSender('REVIEWER');
				$rating->setStatus(2);
				$rating->setHonesty($starH);
				$rating->setMarketKnowldege($starL);
				$rating->setSoldPrice($starH);
				$rating->setResponsiveness($starR);
				$rating->setService($starM);
				$rating->setSoldQuickly($starQ);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($rating);
				$em->flush();
				$reviewId=$rating->getId();
$confirmationLink= "$website_url/confirmation/".$reviewId;
				$messageAdmin = \Swift_Message::newInstance()
		    		->setSubject('Review')
		    		->setFrom($emailReviewer)
		   		->setTo($gbl_email_administrator)
		   		->setBody($this->renderView('RARWebBundle:Email:admin.txt.twig', array('firstname'=>$firstname,'lastname'=>	$lastname,'confirmationLink'=>$confirmationLink,'writereview'=>$writereview,'stars'=>$stars)));
		$this->get('mailer')->send($messageAdmin);
			
				return $this->render('RARWebBundle:Page:sendReview.html.twig');
			}  

 		$website_url = $this->container->getParameter('website_url');
 			
 			
		if ($request->getMethod() == 'POST') 
	    	{   
			$fbFirstName=$this->get('request')->request->get('fbFirstName');
			
			$fbLastName=$this->get('request')->request->get('fbLastName');
			$fbUserFullName=$this->get('request')->request->get('fbUserFullName');
			$fbEmail=$this->get('request')->request->get('fbEmail');

			if($fbEmail=="")
			{
				$firstname=$this->get('request')->request->get('firstname');
				$lastname=$this->get('request')->request->get('lastname');
				$email=$this->get('request')->request->get('email');
				$stars=$this->get('request')->request->get('stars');
			$starH=$this->get('request')->request->get('starH');
			$starR=$this->get('request')->request->get('starR');
			$starM=$this->get('request')->request->get('starM');
			$starL=$this->get('request')->request->get('starL');
		   	$starG=$this->get('request')->request->get('starG');
			$starQ=$this->get('request')->request->get('starQ');
			$headline=$this->get('request')->request->get('headline');
			$writereview=$this->get('request')->request->get('writereview');
			$agent=$this->get('request')->request->get('agent');
			$receiveUpdates= $this->get('request')->request->get('receiveUpdates')=='on' ? 1 : 0;
			$recommend=$this->get('request')->request->get('recommend');
			$type=3;
			$status=2;
			$sysPwd= $this->generateRandomString(8);
			$em = $this->getDoctrine()
	       		->getEntityManager();
	    		$repository = $em->getRepository('RARAdminBundle:User');
	    		$user = $repository->findOneBy(array('email' => $email));
			$reviewer=new User();
			$reviewer->setFirstName($firstname);
			$reviewer->setLastName($lastname);
			$reviewer->setEmail($email);
			$reviewer->setPassword(md5($sysPwd));
			$reviewer->setType($type);
			$reviewer->setStatus($status);
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($reviewer);
			$em->flush();
			$reviewerId=$reviewer->getId();

			$website_url = $this->container->getParameter('website_url');//get website url
			$confirmationLink= $website_url."/confirmed/registration/".$reviewerId;//registration link
			/*---Start - Swift mailer for registration -----*/
			$message = \Swift_Message::newInstance()
		    	->setSubject('Registration')
		    	->setFrom('support@reviewanairline.com')
		    	->setTo($email)
		   	->setBody($this->renderView('RARWebBundle:Email:registration.txt.twig', array('firstname'=>$firstname,'lastname'=>$lastname,'email' => $email,'password'=>$sysPwd,'confirmationLink'=>$confirmationLink)));
		$this->get('mailer')->send($message);	


			$reviewDateTime = new DateTime();
			$rating=new Review();
			$rating->setHeadline($headline);
			$rating->setDescription($writereview);
			$rating->setUseAgent($agent);
			$rating->setReceiveUpdates($receiveUpdates);
			$rating->setRecomendAgent($recommend);
			$rating->setRating($stars);
			$rating->setReviewerId($reviewerId);
			$rating->setcreationTimestamp($reviewDateTime);
			if($realtorId)
			{
				$rating->setRealtorId($realtorId);
			}
			else
			{
				$rating->setRealtorId($id);
			}
				
			$rating->setSender('REVIEWER');
			$rating->setStatus(2);
			$rating->setHonesty($starH);
			$rating->setMarketKnowldege($starL);
			$rating->setSoldPrice($starH);
			$rating->setResponsiveness($starR);
			$rating->setService($starM);
			$rating->setSoldQuickly($starQ);
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($rating);
			$em->flush();
			$confirmationLink= "$website_url/confirmation/".$reviewerId;
			$date=date("Y/m/d.");
			$message = \Swift_Message::newInstance()
            		->setSubject('Review')
            		->setFrom($email)
            		->setTo($gbl_email_administrator)
            		->setBody($this->renderView('RARWebBundle:Email:review.txt.twig', array('firstname'=>$firstname,'lastname'=>$lastname,'email'=>$email,'confirmationLink'=>$confirmationLink,'writereview'=>$writereview,'stars'=>$stars)));
        $this->get('mailer')->send($message);
     return $this->render('RARWebBundle:Page:confirmationLink.html.twig');
				
			}
			else
			{
				$firstname=$fbFirstName;
				$lastname=$fbLastName;
				$email=$fbEmail;
				$fbId=$this->get('request')->request->get('user_form_email');
			}
			$stars=$this->get('request')->request->get('stars');
			$starH=$this->get('request')->request->get('starH');
			$starR=$this->get('request')->request->get('starR');
			$starM=$this->get('request')->request->get('starM');
			$starL=$this->get('request')->request->get('starL');
		   	$starG=$this->get('request')->request->get('starG');
			$starQ=$this->get('request')->request->get('starQ');
			$headline=$this->get('request')->request->get('headline');
			$writereview=$this->get('request')->request->get('writereview');
			$agent=$this->get('request')->request->get('agent');
			$receiveUpdates= $this->get('request')->request->get('receiveUpdates')=='on' ? 1 : 0;
			$recommend=$this->get('request')->request->get('recommend');
			$type=3;
			$status=1;
			$sysPwd= $this->generateRandomString(8);
			$em = $this->getDoctrine()
	       		->getEntityManager();
	    		$repository = $em->getRepository('RARAdminBundle:User');
	    		$user = $repository->findOneBy(array('email' => $email));
			$reviewer=new User();
			$reviewer->setFirstName($firstname);
			$reviewer->setLastName($lastname);
			$reviewer->setEmail($email);
			$reviewer->setPassword(md5($sysPwd));
			$reviewer->setType($type);
			$reviewer->setStatus($status);
			$reviewer->setfacebookId($fbId);
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($reviewer);
			$em->flush();
			$reviewerId=$reviewer->getId();
			$session = $this->getRequest()->getSession(); 
			$session->set('userId', $reviewerId);  
			$session->set('userEmail', $email);
          		$session->set('userName', $firstname);
          	 	$session->set('userType', $type);	
  			 $facebookRepository = $em->getRepository('RARAdminBundle:User');
			$userLogin = $facebookRepository->findOneBy(array('email' => $email, 'facebook_id' =>$fbId,'type'=>3,'status'=>1));
			
			
   
			/* code for mail send for review to realtor first time end here */
			$reviewDateTime = new DateTime();
			$rating=new Review();
			$rating->setHeadline($headline);
			$rating->setDescription($writereview);
			$rating->setUseAgent($agent);
			$rating->setReceiveUpdates($receiveUpdates);
			$rating->setRecomendAgent($recommend);
			$rating->setRating($stars);
			$rating->setReviewerId($reviewerId);
			$rating->setcreationTimestamp($reviewDateTime);
			$rating->setRealtorId($id);
			$rating->setSender('REVIEWER');
			$rating->setStatus(2);
			$rating->setHonesty($starH);
			$rating->setMarketKnowldege($starL);
			$rating->setSoldPrice($starH);
			$rating->setResponsiveness($starR);
			$rating->setService($starM);
			$rating->setSoldQuickly($starQ);
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($rating);
			$em->flush();
			$confirmationLink= "$website_url/confirmation/".$reviewerId;
			$date=date("Y/m/d.");

			$message = \Swift_Message::newInstance()
            		->setSubject('Review')
            		->setFrom($email)
            		->setTo($gbl_email_administrator)
            		->setBody($this->renderView('RARWebBundle:Email:review.txt.twig', array('firstname'=>$firstname,'lastname'=>$lastname,'email'=>$email,'confirmationLink'=>$confirmationLink,'writereview'=>$writereview,'stars'=>$stars)));
        $this->get('mailer')->send($message);
     
			/* code for mail send for review information end here */

		if($userLogin)
			{
				return $this->redirect($this->generateUrl('rar_web_homepage'));   
			}	
					
	 	}
	 
		return $this->render('RARWebBundle:Page:confirmationLink.html.twig');
	 }
	 /*---- End Function -- Capture email of Reviewer -----*/

	 /*---- Start Function --Genrate random number -----*/
	function generateRandomString($length) 
	{
    	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	$randomString = '';
    	for ($i = 0; $i < $length; $i++) 
    	{
        	$randomString .= $characters[rand(0, strlen($characters) - 1)];
    	}
    	return $randomString;
	}
	/*---- End Function --Genrate random number -----*/
	
	/*---- Start Function -Confirm Email -----*/
	public function confirmationEmailAction(Request $request,$id)
	{	
	  	$em = $this->getDoctrine()
	    ->getEntityManager();
		$plan = $em->createQueryBuilder() 
		->select('Review')
		->update('RARWebBundle:Review',  'Review')
		->set('Review.status', ':status')
		->setParameter('status', 1)
		->where('Review.reviewer_id = :id')
		->setParameter('id', $id)
		->getQuery()
		->getResult(); 
   		return $this->render('RARWebBundle:Page:confirmationEmail.html.twig');
	}	
	 /*---- End Function -Confirm Email -----*/


	/*---- Start Function -Verify facebook Account -----*/
	public function verifyfbaccountAction(Request $request)
    {
      	$facebookid=$request->request->get('facebookid');
      	$user = $this->repo('User')->findOneBy(array('facebookid' => $facebookid));
      	if($user)
      	{
      		$message = '';
      	}
      	else
      	{     
      		$src=$request->request->get('imgurl');
      		$data = file_get_contents($src);
      		$message = '';
      	}
     
      	return new Response($message);
    }
    /*---- End Function -Verify facebook Account -----*/

     /*---- Start Function -View Detail of Property -----*/
	public function propertySearchDetailAction($id)
 	{

 		$em = $this->getDoctrine()
        ->getEntityManager();
    	$repository = $em->getRepository('RARWebBundle:Property');

 		$em = $this->getDoctrine()->getEntityManager();
        $property = $em->getRepository('RARWebBundle:Property')->find($id);
        if (!$property) 
        {

            throw $this->createNotFoundException('Unable to find  video.');
        }
 		$em = $this->getDoctrine()
		->getEntityManager();
		$propertyImages = $em->createQueryBuilder()
		->select('PropertyImages')
		->from('RARWebBundle:PropertyImages',  'PropertyImages')
		->leftJoin('RARWebBundle:Property', 'property', "WITH", "property.id=PropertyImages.property_id")
		->where('property.id = :userId')
		->setParameter('userId', $id)
		->getQuery()
		->getResult();
		
		$realtorInfo = $em->createQueryBuilder()
		->select('realtors')
		->from('RARAdminBundle:User',  'realtors')
		->innerJoin('RARWebBundle:Property', 'property', "WITH", "property.user_id=realtors.id")
		->where('property.id = :userId')
		->setParameter('userId', $id)
		->getQuery()
		->getResult();
		//echo "<pre>";print_r($property);die;
		$ad= $property->address;
 		$latitude = '';
		$longitude = '';
		$iframe_width = '400px';
		$iframe_height = '300px';
		$address = $ad;
	
		$address = urlencode($address);
		//$key = "AIzaSyBWrzHwyPiksZMYSUcw1pAEIDGDrGjBRn8";
		$key="AIzaSyCrLkDN07F73NgDl0F7j5bxW7D_7f2wD-s";
		$url = "http://maps.google.com/maps/geo?q=".$address."&output=json&key=".$key;
		$ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]); // Comment out the line below if you receive an error on certain hosts that have security restrictions
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		$geo_json = json_decode($data, true);
		if ($geo_json['Status']['code'] == '200') 
		{
		$latitude = $geo_json['Placemark'][0]['Point']['coordinates'][0];
		$longitude = $geo_json['Placemark'][0]['Point']['coordinates'][1]; 
	   	}

		//echo "<PRE>";print_r($propertyImages);die;
		if( isset($propertyImages) && is_array($propertyImages) && count($propertyImages) > 0 )
		{
			$defaultPropertyImage = '';
		}
		else
		{
			$defaultPropertyImage = '<img src="http://www.bwcmultimedia.com/PS/review-a-realtor/web/Property/property_default.jpeg" alt="Property" style="height:40%;width:100%"/>';
		}

        return $this->render('RARWebBundle:Page:propertySearchDetail.html.twig', array(
            'property'      => $property,'propertyImages'=>$propertyImages,'latitude'=>$latitude,'longitude'=>$longitude,'iframe_width'=>$iframe_width,'iframe_height'=>$iframe_height,'address'=>$address,'key'=>$key,'url'=>$url,'ch'=>$ch,'realtorInfo'=>$realtorInfo, 'defaultPropertyImage'=>$defaultPropertyImage
        ));
    }
    /*---- End Function ---View Detail of Property -----*/
    
    /*---- Start Function -Login for Property Claim -----*/
	public function claimLoginAction(Request $request,$id)
	{
		$gbl_email_support = $this->container->getParameter('gbl_email_support');
		$session = $this->getRequest()->getSession();
		if( $session->get('userId') && $session->get('userId') != '' )
		{
			$em = $this->getDoctrine()
	  		->getEntityManager();
	  		$repository = $em->getRepository('RARAdminBundle:User');
			$property=$this->get('request')->request->get('hidPropertyId');
	      
			$em = $this->getDoctrine()
			->getEntityManager();
			$realtor=new Claim();
			$realtor->setCurrentOwner($id);
			$realtor->setPropertyId($property);
			$realtor->setType('Property');
			$realtor->setStatus(2);
			$realtor->setClaimedBy($session->get('userId'));
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($realtor);
			$em->flush(); 
			
			$realtorName = $em->createQueryBuilder()
			->select('real')
			->from('RARAdminBundle:User',  'real')
			->Where('real.id=:realtor')
			->setParameter('realtor',$session->get('userId'))
			->getQuery()
			->getArrayResult();
			
			$firstName = $realtorName[0]['first_name'];
			$lastName = $realtorName[0]['last_name'];
			$email = $realtorName[0]['email'];
			/*$date=date("Y/m/d.");
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= 'From: <support@review-a-realtor.com>' . "\r\n";
			$to = $email;
			$subject = "Claim Listing";
			$txt='Hello '. $firstName.' '. $lastName .',<br><br>You have requested claim listing on '.$date;
	  		mail($to,$subject,$txt,$headers); //send mail    */

			$message = \Swift_Message::newInstance()
            				->setSubject('Claim')
            				->setFrom($gbl_email_support)
            				->setTo($email)
            				->setBody($this->renderView('RARWebBundle:Email:claimProperty.txt.twig', array('firstname'=>$firstName,'lastname'=>$lastName))); 
			return $this->render('RARWebBundle:Page:claimR.html.twig');
		}   
	}
	 /*---- End Function -Login for Property Claim -----*/

	 /*---- Start Function -Email Exist in database -----*/
	public function checkUserExistanceAction(Request $request,$id)
	{
		$email = $_POST['email'];
		$password = $_POST['password'];
		$gbl_email_support = $this->container->getParameter('gbl_email_support');
		$em = $this->getDoctrine()
	    	->getEntityManager();
		$repository = $em->getRepository('RARAdminBundle:User');
		if ($request->getMethod() == 'POST')
	 	{
	 		$email = $request->get('email');
	    		$password = md5($request->get('password'));
	 		$user = $repository->findOneBy(array('email' => $email, 'password' => $password,   'status'=>1 ));
	 	 	if ($user) 
        		{
        			if($this->get('request')->request->get('hidPropertyId'))
		      			$property=$this->get('request')->request->get('hidPropertyId');
		  		else
			  		$property=$_POST['hidPropertyId'];
			      		$name=$user->getFirstName();
					$em = $this->getDoctrine()
					->getEntityManager();
					
					$realtor=new Claim();
					$realtor->setCurrentOwner($id);
					$realtor->setPropertyId($property);
					$realtor->setType('Property');
					$realtor->setStatus(2);
					$realtor->setClaimedBy($user->getId());
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($realtor);
					$em->flush(); 
					$firstName=$user->getFirstName();
					$lastName=$user->getLastName();
			$message = \Swift_Message::newInstance()
            				->setSubject('Claim')
            				->setFrom($gbl_email_support)
            				->setTo($email)
            				->setBody($this->renderView('RARWebBundle:Email:claimProperty.txt.twig', array('firstname'=>$firstName,'lastname'=>$lastName)));
        				$this->get('mailer')->send($message);

				$session = $this->getRequest()->getSession(); 
				
				$session->set('userId', $user->getId());  
				$session->set('userEmail', $email);


					return new response('SUCCESS');
			}
 			else 
       		{
          		$html = '';
				$html.='Invalid Username/Password';
				return new response($html);
        	}
		
		}

	}
	 /*---- End Function -Email Exist in database -----*/

	public function successfulClaimAction(Request $request)
	{
		
		
	return $this->render('RARWebBundle:Page:claimR.html.twig');

	}

	/*---- Start Function Show Cms-----*/
	public function showCmsAction(Request $request)
	{  		
		$html='';
		$em = $this->getDoctrine()->getEntityManager();
		$cms = $em->createQueryBuilder()
		->select('cms')
		->from('RARAdminBundle:CMS',  'cms')
		->where('cms.type=1')
		->getQuery()
		->getResult();
		
		foreach ($cms as $cms)
		{
  			$html.='<li>'.'<a href="/review-a-realtor/web/page'.$cms->id.'">'.$cms->name.'</a>'.'</li>';
		}
		return new response($html); 

	}
	 /*---- End Function--Show Cms-----*/

	 /*---- Start Function Showcontent of Pages-----*/
	public function pageAction(Request $request,$id)
	{  
		$em = $this->getDoctrine()->getEntityManager();
		$cms = $em->createQueryBuilder()
		->select('cms.content,cms.name')
		->from('RARAdminBundle:CMS',  'cms')
		->where('cms.id=:id')
		->andwhere('cms.type=1')			
		->setParameter('id',$id)
		->getQuery()
		->getArrayResult();
		return $this->render('RARWebBundle:Page:page.html.twig',array('cms'=>$cms)); 

	
	}
	 /*---- End Function Show content of Pages-----*/

	
	 /*---- End Function Show Banner-----*/
	  /*---- Start Function -----Paypal Success-----*/
	public function successPaymentAction(Request $request)
	{
		$gbl_email_support = $this->container->getParameter('gbl_email_support');
		$raw_post_data = file_get_contents('php://input');		
		$raw_post_array = explode('&', $raw_post_data);
		$myPost = array();
		foreach ($raw_post_array as $keyval) {
		$keyval = explode ('=', $keyval);
		if (count($keyval) == 2)
			$myPost[$keyval[0]] = urldecode($keyval[1]);
			}
			// read the post from PayPal system and add 'cmd'
			$req = 'cmd=_notify-validate';
			if(function_exists('get_magic_quotes_gpc')) 
			{
			   $get_magic_quotes_exists = true;
			} 
			foreach ($myPost as $key => $value) {        
			if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) 
			{ 
				$value = urlencode(stripslashes($value)); 
			} 
			else 
			{
				$value = urlencode($value);
			}
			   $req .= "&$key=$value";
			}
			// STEP 2: Post IPN data back to paypal to validate
			$ch = curl_init('https://www.sandbox.paypal.com/cgi-bin/webscr');
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
			if( !($res = curl_exec($ch)) ) 
			{
				// error_log("Got " . curl_error($ch) . " when processing IPN data");
				curl_close($ch);
				exit;
			}
			curl_close($ch);
			// STEP 3: Inspect IPN validation result and act accordingly
			if (strcmp($res, "VERIFIED") == 0) 
			{		

				$session = $this->getRequest()->getSession(); 
				$firstname=$session->get('firstname');
				$lastname=$session->get('lastname');
				$email=$session->get('email');
				$password=$session->get('password');
				$phone=$session->get('phone');
				$state=$session->get('state');
				$address=$session->get('address');
				$address2=$session->get('address2');
				$city=$session->get('city');
				$fax=$session->get('fax');
				$business=$session->get('business');
				$overview=$session->get('overview');
				$twitter=$session->get('twitter');
				$google=$session->get('google');
				$linkedin=$session->get('linkedin');
				$video=$session->get('video');
				$id=$session->get('id');
				$planid=$session->get('planid');
				$logo=$session->get('logo');
				$file=$session->get('file');
				$facebook=$session->get('facebook');
				$pincode=$session->get('pincode');
				$country='US'; // set country USA
				$type=2;
				$status=1;	
		
				$realtor=new User();
				$realtor->setFirstName($firstname);
				$realtor->setLastName($lastname);
				$realtor->setPassword(md5($password));
				$realtor->setEmail($email);
				$realtor->setPhone($phone);
				$realtor->setState($state);
				$realtor->setAddress($address);
				$realtor->setAddress2($address2);
				$realtor->setCity($city);
				$realtor->setCountry($country);
				$realtor->setType($type);
				$realtor->setStatus($status);
				$realtor->setPinCode($pincode);
				$realtor->setFax($fax);
				$realtor->setBusinessName($business);
				$realtor->setOverview($overview);
				$realtor->setPlanId($planid);
				if(isset($logo) && $logo!="")
				{
					$realtor->setLogo($logo);
				}
				else
				{
					$realtor->setLogo('company.jpeg');
				}
				$realtor->setTwitter($twitter);
				$realtor->setFacebook($facebook);
				$realtor->setGoogle($google);
				$realtor->setLinkedin($linkedin);
				$realtor->setVideo($video);
				if(isset($file) && $file!="")
				{
					$realtor->setImage($file);
				}
				else
				{
					$realtor->setImage('default_user_image.jpeg');
				}
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($realtor);
				$em->flush(); 
				
				$reviewerId=$realtor->getId();

				$website_url = $this->container->getParameter('website_url');//get website url
				$confirmationLink= $website_url."/confirmed/registration/".$reviewerId;//registration link
				$message = \Swift_Message::newInstance()
            			->setSubject('Registration')
            			->setFrom($gbl_email_support)
            			->setTo($email)
            			->setBody($this->renderView('RARWebBundle:Email:registration.txt.twig', array('firstname'=>$firstname,'lastname'=>$lastname,'Password'=>$password,'email'=>$email,'confirm')));
        			$this->get('mailer')->send($message);

				mail($to,$subject,$txt,$headers); //send mail 
				$realtorId=$realtor->getId();
				$item_name = $_POST['item_name'];
				$item_number = $_POST['item_number'];
				$payment_status = $_POST['payment_status'];
				$payment_amount = $_POST['mc_gross'];
				$payment_currency = $_POST['mc_currency'];
				$txn_id = $_POST['txn_id'];
				$receiver_email = $_POST['receiver_email'];
				$payer_email = $_POST['payer_email'];
				$address= $_POST['address_street'];
				$zipcode = $_POST['address_zip'];
				$state = $_POST['address_state'];
				$city= $_POST['address_city'];
				$country = $_POST['address_country'];
				$datefields=$_POST["payment_date"];
	   			$time=$datefields[0];
	   			if($item_name == 'Half-Yearly')
	   			{
	   				$recuringPeriod = 2;
	   			}
	   			elseif($item_name == 'Yearly')
	   			{
	   				$recuringPeriod = 3;
	   			}
	   			elseif($item_name == 'Monthly')
	   			{
	   				$recuringPeriod = 1;
	   			}
	   		
				$payment=new Payment();
				$payment->setAmount($payment_amount);
				$payment->setTransactionId($txn_id);
				$payment->setPlanId($planid);
				$payment->setUserId($realtorId);
				$payment->setRecuringPeriod($recuringPeriod);
				//$payment->setCreationTimeStamp($datefields);
				$em->persist($payment);
				$em->flush(); 
				   	
				$message = \Swift_Message::newInstance()
            			->setSubject('Payment')
            			->setFrom($gbl_email_support)
            			->setTo($email)
            			->setBody($this->renderView('RARWebBundle:Email:payment.txt.twig', array('firstname'=>$firstname,'lastname'=>$lastname,'amount'=>$payment_amount,'transactionId'=>$txn_id,'email'=>$email,'datefields'=>$datefields)));
        			$this->get('mailer')->send($message);   	
				/*$date=date("Y/m/d.");
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				$headers .= 'From: <support@review-a-realtor.com>' . "\r\n";
				$to = $payer_email;
				$subject = "Payment detail/reviewarealtor.com";
				$txt='Hello '. $firstname.' '. $lastname.',<br><br>You have selected the premium plan on reviewarealtor.com on  '.$datefields.'<br><br>Your transaction Details are as under: <br>Amount: <b>'.'$'.$payment_amount.'</b><br>TransactionId: <b>'.	$txn_id.'</b>';
				mail($to,$subject,$txt,$headers); //send mail*/

     		}   	
		return $this->render('RARWebBundle:Page:registerus.html.twig');
	}	
	 /*---- End Function -----Paypal Success-----*/

	 /*---- Start Function--Show Privacy Policy Page-----*/ 
	public function privacyPolicyAction(Request $request)
	{

	return $this->render('RARWebBundle:Page:privacyPolicy.html.twig');

	}
	/*---- End Function--Show Privacy Policy Page-----*/ 
	
	/*---- Start Function--Show AboutUs Policy Page-----*/ 
	public function aboutUsAction(Request $request)
	{

	return $this->render('RARWebBundle:Page:aboutUs.html.twig');

	}
	/*---- End Function--Show AboutUs Policy Page-----*/ 

	/*---- Start Function--Show Site Map Page-----*/ 
	public function siteMapAction(Request $request)
	{

	return $this->render('RARWebBundle:Page:siteMap.html.twig');

	}
	/*---- End Function--Show Site Map Page-----*/ 

	/*---- Start Function--Show terms Page-----*/ 
	public function termsAction(Request $request)
	{

	return $this->render('RARWebBundle:Page:terms.html.twig');

	}
	/*---- End Function--Show terms Page-----*/

	/*---- Start Function--Show Contact Page-----*/
	public function faqAction(Request $request)
	{

	return $this->render('RARWebBundle:Page:faq.html.twig');

	}
	/*---- End Function--Show Contact Page-----*/

	/*---- Start Function--Show Disclaimer Page-----*/
	public function disclaimerAction(Request $request)
	{

	return $this->render('RARWebBundle:Page:disclaimer.html.twig');

	}
	/*---- End Function--Show Disclaimer Page-----*/
	/*---- Start Function--Email Subscriber-----*/
	public function SubscriberAction(Request $request)
	{
		$subscribe = $request->get('subscribeEmail');
		$subscribed=new Subscriber();
		$subscribed->setEmail($subscribe);
		$em = $this->getDoctrine()->getEntityManager();
		$em->persist($subscribed);
		$em->flush(); 
		return $this->render('RARWebBundle:Page:subscribe.html.twig',array('subscribe'=>$subscribe));

	}
/*---- End Function--Email Subscriber-----*/

public function uniqueEmailAction(Request $request)
{
$email=$_POST['email'];
$em = $this->getDoctrine()->getEntityManager();
$repository = $em->getRepository('RARAdminBundle:User');        
$user = $repository->findOneBy(array('email' => $email));
//$html='Email already exists';
if($user)
{
return new response('SUCCESS');
}	

return new response('FAILURE');

}

/*--------------------------------------------------Update Starts Here-------------------------------------------------------------*/

	/*---Start Function-- Write ReviewPage---*/
	public function writeReviewAction()
    	{
		$id=0;
        	return $this->redirect($this->generateUrl('rar_web_review',array('id'=>$id)));
    	}
     	/*---End Function-- Write ReviewPage---*/

/*----Start Function -  Get Airline Detail-----*/
	public function airlineDetailAction()
	{
		$html='';
		$a = $_POST['stateCode'];
		$em = $this->getDoctrine()->getEntityManager();
		$airlines = $em->createQueryBuilder()
		->select("airline")
		->from("RARAdminBundle:User", "airline") 
		->where('airline.business_name = :businessName')
		->setParameter('businessName', $a)
		->getQuery()
		->getArrayResult();
		$url = $_SERVER['DOCUMENT_ROOT'].$_SERVER['REQUEST_URI'];
		$arrUrl = explode('airlineDetail', $url);
		$url = $arrUrl[0];
		$image = $this->container->getParameter('image_url');
		$html.='
			<div class="image-box" id="divImgBox">
          		<div class="image" style="height:132px; width:171px;">';
	if( file_exists($url."uploads/".$airlines[0]['logo']) )
		$html.='<img id="airlineLogo" src="'.$image.'uploads/'.$airlines[0]['logo'].'"  alt="image not found" class="large" />';
		
	else
	{
		if( file_exists($url."uploads".$airlines[0]['logo']) )
			$html.='<img id="airlineLogo" src="'.$image.'uploads/'.$airlines[0]['logo_tile'].'"  alt="image not found" class="large" />';

		else
			$html.='<img id="airlineLogo" src="'.$image.'uploads/default_user_image.jpeg"  alt="image not found" class="large" />';
	}
 $html.='</div>
      </div>

	<div class="content-box" id="divRatingBox">
<input type=hidden  name="realtorReview" value='.$airlines[0]['id'].'>
     <div itemprop="name" id="agent-to-review-name">'.$airlines[0]['business_name'].'</div>
        <div id="agent-to-review-agency">'.$airlines[0]['category1'].'</div>
        <div id="agent-overall-rating-wrapper ">
          <div class="review-star-line " id="agent-overall-rating">
            <div class="star-group-left"> 
            <div id="ratingsForm">
							<div class="stars">
								<input type="radio" name="stars" class="star-1" id="star-11" value=1 />
								<label class="star-1" for="star-11">1</label>
								<input type="radio" name="stars" class="star-2" id="star-12" value=2 />
								<label class="star-2" for="star-12">2</label>
								<input type="radio" name="stars" class="star-3" id="star-13" value=3 />
								<label class="star-3" for="star-13">3</label>
								<input type="radio" name="stars" class="star-4" id="star-14" value=4 />
								<label class="star-4" for="star-14">4</label>
								<input type="radio" name="stars" class="star-5" id="star-15" value=5 />
								<label class="star-5" for="star-15">5</label>
							
								<span></span>
								</div>
									</div>
			</div>
		  </div>
		  
		
          <div id="star-rating-text" style="font-size:14px;"><b>Click the stars to rate airline!</b></div>
        </div>
      </div>';
      

return new response($html);
}
/*----End Function -  Get Airline Detail-----*/

public function getAirlineNameAction()
	{
		$searchAirline = $_POST['id'];
		$html='';
		$html.='<ul>';
		$em = $this->getDoctrine()->getEntityManager();
 		$airlineName = $em->createQueryBuilder()
	      	->select('user')
	      	->from('RARAdminBundle:User',  'user')
		->where('user.business_name like :airName')
		->setParameter('airName', $searchAirline.'%')
		->setMaxResults(40)	
	      	->getQuery()
	      	->getArrayResult(); 
		foreach($airlineName as $airline)
		{
			$html.='<li id="'.$airline['business_name'].'" class="ajx_li" onclick="javascript:updateSearchValue(this.id);">'.$airline['business_name'].'</li>';
		}

		$html.='</ul>';
		return new response($html);
	}

	/*---End Function-- get All Airlines ---*/	


/*----Start Function--Approve Registration-----*/
	public function confirmedRegistrationAction($id)
	{
		$em = $this->getDoctrine()
	    	->getEntityManager();
		/*---Start --- Reviewer is confirmed the registration process-----*/
		$confirmedSubscribe = $em->createQueryBuilder() 
		->select('reg')
		->update('RARAdminBundle:User',  'reg')
		->set('reg.status', ':status')
		->setParameter('status', 1)
		->where('reg.id = :id')
		->setParameter('id', $id)
		->getQuery()
		->getResult();
		/*---End --- Reviewer is confirmed the registration process-----*/

		/*---Start --- fetch user detail-----*/
 		$user = $em->createQueryBuilder()
      		->select('user')
      		->from('RARAdminBundle:User',  'user')   	
      		->where('user.id=:id')
      		->setParameter('id', $id)
      		->getQuery()
      		->getArrayResult(); 	
		$email= $user[0]['email'];
		$password = $user[0]['password'];
		/*---End - fetch user detail-----*/

		/*---Start - login when reviewer confirmed registration-----*/
		$repository = $em->getRepository('RARAdminBundle:User');
 		$user = $repository->findOneBy(array('email' => $email,'password' => $password,'status'=>1));	
		$session = $this->getRequest()->getSession();  	
           	$session->set('userId', $user->getId()); 
           	$session->set('userEmail', $user->getEmail());	
        	if ($user) 
        	{
			return $this->redirect($this->generateUrl('rar_web_home'));   

		}
		 return $this->redirect($this->generateUrl('rar_web_home'));   
		/*---End - login when reviewer confirmed registration-----*/

}

/*---Start Function-- get All Airlines ---*/	
	public function getRealtorNameAction()
	{
		$searchRealtor = $_POST['id'];
		$html='';
		$html.='<ul>';
		$em = $this->getDoctrine()->getEntityManager();
 		$realtorName = $em->createQueryBuilder()
	     ->select('user')
	     	->from('RARAdminBundle:User',  'user')
		->where('user.business_name like :realName')

		->setParameter('realName', $searchRealtor.'%')
		->setMaxResults(40)
	      	->getQuery()
	      	->getArrayResult(); 
		foreach($realtorName as $realtor)
		{
			$html.='<li id="'.$realtor['business_name'].'" class="ajx_li" onclick="javascript:updateSearchValue(this.id);">'.$realtor['business_name'].'</li>';
		}

		$html.='</ul>';
		return new response($html);
	}
		function facebookLoginAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			$em = $this->getDoctrine()->getEntityManager();
			$type=3;
			$status=1;
			$fbFirstName=$this->get('request')->request->get('fbFirstName');
			$fbLastName=$this->get('request')->request->get('fbLastName');
			$fbUserFullName=$this->get('request')->request->get('fbUserFullName');
			$fbEmail=$this->get('request')->request->get('fbEmail');
			$fbUserprofpic=$this->get('request')->request->get('fbpimage');
			$fbId=$this->get('request')->request->get('user_form_email');
			$session->set('fbId', $fbId);
			$session->set('fbUserprofpic', $fbUserprofpic); 
			/*---Start - Add new Facebook User -----*/
			$reviewer=new User();
			$reviewer->setFirstName($fbFirstName);
			$reviewer->setLastName($fbLastName);
			$reviewer->setEmail($fbEmail);
			$reviewer->setImage($fbUserprofpic);
			$reviewer->setfacebookId($fbId);
			$reviewer->setType($type);
			$reviewer->setStatus($status);
			$em->persist($reviewer);
			$em->flush();
			/*---End - Add new Facebook User -----*/
			
			/*---Start - Facebook LoggedIn -----*/
			$reviewerId=$reviewer->getId();
			$session = $this->getRequest()->getSession(); 
			$session->set('userId', $reviewerId);  
			$session->set('userEmail', $fbEmail);
          		$session->set('userName', $fbFirstName);
          	 	$session->set('userType', $type);	
  			$facebookRepository = $em->getRepository('RARAdminBundle:User');
			$userLogin = $facebookRepository->findOneBy(array('email' => $fbEmail, 'facebook_id' =>$fbId,'type'=>3,'status'=>1));
			if($userLogin)
			{
				return $this->redirect($this->generateUrl('rar_web_homepage'));   

			}
			/*---End - Facebook LoggedIn -----*/
			return $this->redirect($this->generateUrl('rar_web_homepage'));   

	}

/*----Start Function -  Show  Cms for footer-----*/	
		public function showCmsFooterAction(Request $request)
		{  		
		$html='';
		$arrUrl = explode('web', $_SERVER['PHP_SELF']);
		$em = $this->getDoctrine()->getEntityManager();
		$cms = $em->createQueryBuilder()
		->select('cms')
		->from('RARAdminBundle:CMS',  'cms')
		->where('cms.type=2')
		->getQuery()
		->getResult();	
		foreach ($cms as $cms)
		{
  			$html.='<li>'.'<a href="'.$this->container->getParameter('website_url').$cms->url.'">'.$cms->name.'</a>'.'</li>';
		}
	
		$html.='<li><a href="'.$this->container->getParameter('website_url').'contact-us">Contact Us </a></li>';
		return new response($html); 

	}
	/*----End Function -  Show  Cms for footer-----*/
	
	/*----Start Function -  Show  Content for footer Cms-----*/
	public function pageFooterAction(Request $request,$id)
	{  
		$url = $id;
		$em = $this->getDoctrine()->getEntityManager();
		$cms = $em->createQueryBuilder()
		->select('cms')
		->from('RARAdminBundle:CMS',  'cms')
		->where('cms.url=:url')
		->setParameter('url', $url)
		->getQuery()
		->getResult();
			
		foreach ($cms as $cms);
		$id = $cms->id;
		$em = $this->getDoctrine()->getEntityManager();
		$cms = $em->createQueryBuilder()
		->select('cms')
		->from('RARAdminBundle:CMS',  'cms')
		->where('cms.id=:id')			
		->setParameter('id',$id)
		->getQuery()
		->getArrayResult();

		return $this->render('RARWebBundle:Page:footerPage.html.twig',array('cms'=>$cms)); 

	
	}
	/*----Start Function -  Show  Content for footer Cms-----*/



	public function reviewerProfileAction(Request $request,$id)
	{

		$senderType = "REVIEWER";
		$em = $this->getDoctrine()->getEntityManager();
		$reviewsWr = $em->createQueryBuilder()
			->select('user.business_name,airline.description,airline.headline,user.image,airline.rating,user.logo,user.first_name,user.email')
			->from('RARWebBundle:Review',  'airline')
			->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=airline.realtor_id ")
			->where('airline.reviewer_id=:id')		
			->andwhere('airline.status=1')		
			->andwhere('airline.sender=:sender')	
		
			->setParameter('id',$id)
			->setParameter('sender',$senderType)
			->getQuery()
			->getArrayResult();

		$reviewerName = $em->createQueryBuilder()
			->select('user.business_name,airline.description,airline.headline,user.image,airline.rating,user.logo,user.first_name,user.email')
			->from('RARWebBundle:Review',  'airline')
			->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=airline.reviewer_id ")
			->where('airline.reviewer_id=:id')		
			->andwhere('airline.status=1')		
			->andwhere('airline.sender=:sender')	
		
			->setParameter('id',$id)
			->setParameter('sender',$senderType)
			->getQuery()
			->getArrayResult();
			$reviewerrName = $reviewerName[0]['first_name'];
			$reviewerEmail = $reviewerName[0]['email'];
			$reviewerImage = $reviewerName[0]['image'];

	
			$reviews = $em->createQueryBuilder()
			->select('Review.description,user.first_name,user.id,user.last_name,user.business_name,Review.realtor_id,Review.creation_timestamp,Review.reviewer_id,Review.rating,Review.id,Review.headline')
			->from('RARWebBundle:Review',  'Review')
			->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=Review.realtor_id")
			//->where('Review.realtor_id=:id')
			->andwhere('Review.sender=:type')
			->andwhere('Review.status=1')
			->setParameter('type', $senderType)			
			//->setParameter('id',$id)
			->addOrderBy('Review.id', 'DESC')
			->setMaxResults(3)
			->getQuery()
			->getResult();
		//echo"<pre>";print_r($reviews);die;
			$arrReviewer = array();
			$repository = $em->getRepository('RARAdminBundle:User');
			foreach($reviews as $reviewer)
			{
				$reviewId = $reviewer['id'];
				$arrReviewer[$reviewId]['first_name'] =  $reviewer['first_name'] ;
				$arrReviewer[$reviewId]['id'] =  $reviewer['id'] ;
				$arrReviewer[$reviewId]['headline'] =  $reviewer['headline'] ;
				$arrReviewer[$reviewId]['rating'] =  $reviewer['rating'] ;
				$arrReviewer[$reviewId]['creation_timestamp'] =  $reviewer['creation_timestamp'] ;
				$arrReviewer[$reviewId]['last_name'] =  $reviewer['last_name'] ;
				$arrReviewer[$reviewId]['description'] =  $reviewer['description'] ;
				$arrReviewer[$reviewId]['business_name'] =  $reviewer['business_name'] ;
				$arrReviewer[$reviewId]['realtor_id'] =  $reviewer['realtor_id'] ;
				$arrReviewer[$reviewId]['reviewer_id'] =  $reviewer['reviewer_id'] ;
				
				$user = $repository->findOneBy(array('id' =>  $reviewer['reviewer_id']));
				if($user)	
					$realtorName=$user->getFirstName()." ".$user->getLastName();
				else
					$realtorName='';
					$arrReviewer[$reviewId]['realtor_name'] =  $realtorName ;
			}

	return $this->render('RARWebBundle:Page:reviewerProfile.html.twig',array('reviewsWr'=>$reviewsWr,'reviewerName'=>$reviewerrName,'reviewerEmail'=>$reviewerEmail,'reviewerImage'=>$reviewerImage,'reviews'=>$arrReviewer));
	
	}
	public function reviewDetailAction(Request $request,$headline)
	{
		$fullUrl = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

		$senderType = "REVIEWER";
		$em = $this->getDoctrine()->getEntityManager();
		$reviewsDetail = $em->createQueryBuilder()
			->select('user.business_name,airline.description,airline.headline,user.image,airline.rating,airline.realtor_id,user.logo,user.first_name,airline.market_knowldege,airline.sold_price,airline.sold_quickly,airline.responsiveness,airline.service,airline.honesty,airline.creation_timestamp')
			->from('RARWebBundle:Review',  'airline')
			->leftJoin('RARAdminBundle:User', 'user',"WITH", "user.id=airline.realtor_id ")
			->where('airline.headline=:id')		
			->andwhere('airline.status=1')		
			->andwhere('airline.sender=:sender')	
			->setParameter('id',$headline)
			->setParameter('sender',$senderType)
			->getQuery()
			->getArrayResult();
//echo "<pre>";print_r($reviewsDetail);die;
			$id = $reviewsDetail[0]['realtor_id'];

			$realtors = $em->getRepository('RARAdminBundle:User')->find($id);
		
        	if (!$realtors) 
        	{
           	 throw $this->createNotFoundException('Unable to find  realtor.');
       		 }

		$review = $em->createQueryBuilder()
     		->select("avg(rev.rating) as avgRating")
     		->from("RARWebBundle:Review", "rev")
		->where('rev.realtor_id = :realtodId')
		->andwhere('rev.status = 1')
		->setParameter('realtodId', $id)
		->getQuery()
		->getArrayResult(); 
//echo "<pre>";print_r($review);die;

	return $this->render('RARWebBundle:Page:reviewDetail.html.twig',array('realtors'=>$realtors,'review'=>$review,'fullUrl'=>$fullUrl,'reviewsDetail'=>$reviewsDetail));
	
	}

	/*----Start Function -  Check Headline  -----*/
	public function checkHeadlineAction(Request $request)
	{
		$reviewHeadline=$_POST['reviewHeadline'];
		$em = $this->getDoctrine()->getEntityManager();
		$repository = $em->getRepository('RARWebBundle:Review');        
		$review = $repository->findOneBy(array('headline' => $reviewHeadline));

		if($review)
		{
			return new response('SUCCESS');
		}	

		return new response('FAILURE');

	}
	/*----End Function -  Check Headline  -----*/


	public function getAdvertiesmentAction(Request $request)
	{
		$html='<div id="myGallery">';
		$em = $this->getDoctrine()->getEntityManager();
		$adv = $em->createQueryBuilder()
		->select('adv')
		->from('RARAdminBundle:Advertiesment',  'adv')
		->where('adv.type=:type')
		->setParameter('type',"ADV")
		->getQuery()
		->getResult();
		foreach($adv as $advertisement)
		{
			$html.='<a href="'.$advertisement->target_url.'"><img src="'.$this->container->getParameter('image_url').'uploads/'.$advertisement->image.'" class="active1" style="width:234px;height:250px;"  alt="Advertisement" class=""/></a>';
		
		}
		$html.='</div>';
		return new response($html);

	}
 /*---- Start Function Show Banner-----*/
	public function getBannerAction(Request $request)
	{
		$html='<div id="myGallery">';
		$em = $this->getDoctrine()->getEntityManager();
		$adv = $em->createQueryBuilder()
		->select('adv')
		->from('RARAdminBundle:Advertiesment',  'adv')
		->where('adv.type=:type')
		->setParameter('type',"BAN")
		->getQuery()
		->getResult();
		foreach($adv as $advertisement)
		{
			
			
			$html.='<a href="'.$advertisement->target_url.'"><img src="'.$this->container->getParameter('image_url').'uploads/'.$advertisement->image.'" class="Cont_css active1"   alt="Advertisement" class=""/></a>';
		
		}
		$html.='</div>';
		return new response($html);

	}


public function searchRealtorAction()
	{
		$searchRealtor = $_POST['id'];
		$html='';
		$html.='<ul>';
		$em = $this->getDoctrine()->getEntityManager();
 		$realtorName = $em->createQueryBuilder()
	      	->select('user')
	      	->from('RARAdminBundle:User',  'user')
		->where('user.business_name like :realName or user.first_name like :fName or user.address like :address')
		//->andwhere('user.first_name like :fName')
		//->andwhere('user.address like :address')
		->setParameter('realName', $searchRealtor.'%')
		->setParameter('fName', $searchRealtor.'%')
		->setParameter('address', $searchRealtor.'%')
		->setMaxResults(40)
	      	->getQuery()
	      	->getArrayResult(); 

		foreach($realtorName as $realtor)
		{
			$html.='<li id="'.$realtor['business_name'].'" class="ajx_li" onclick="javascript:updateSearchValue(this.id);">'.$realtor['business_name'].'</li>';
$html.='<li id="'.$realtor['address'].'" class="ajx_li" onclick="javascript:updateSearchValue(this.id);">'.$realtor['address'].'</li>';
$html.='<li id="'.$realtor['first_name'].'" class="ajx_li" onclick="javascript:updateSearchValue(this.id);">'.$realtor['first_name'].'</li>';
		}

		$html.='</ul>';
		return new response($html);
	}
/*--------------------------------------------------Update Ends Here-------------------------------------------------------------*/

/*public function paginateAction(Request $request)
{
 $em    = $this->get('doctrine.orm.entity_manager');
    $dql   = "SELECT a FROM RARAdminBundle:City a";
    $query = $em->createQuery($dql);

    $paginator  = $this->get('knp_paginator');
    $pagination = $paginator->paginate(
        $query,
        $request->query->get('page', 1),
        10
    );

return $this->render('RARWebBundle:Page:pagination.html.twig',array('pagination' => $pagination));
}
*/


}
