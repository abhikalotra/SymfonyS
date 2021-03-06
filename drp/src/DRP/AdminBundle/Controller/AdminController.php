<?php

namespace DRP\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DRP\AdminBundle\Entity\User;
use DRP\AdminBundle\Entity\Business;
use DRP\AdminBundle\Entity\BusinessType;
use DRP\AdminBundle\Entity\Plan;
use DRP\AdminBundle\Entity\GlobalInstrument;
use DRP\AdminBundle\Entity\RegistrationType;
use DRP\AdminBundle\Entity\Book;
use DRP\AdminBundle\Entity\Lessor;
use DRP\AdminBundle\Entity\Lease;
use DRP\AdminBundle\Entity\Company;
use DRP\AdminBundle\Entity\UserPlan;
use DRP\AdminBundle\Entity\Log;
use DRP\AdminBundle\Entity\RegistrationStatus;
class AdminController extends Controller
{

    /*===Start function for admin login======*/	
    public function loginAction(Request $request)
    {
	
	$session = $this->getRequest()->getSession();
	$number = $this->generateRandomString(10);		
	if( $session->get('userId') && $session->get('userId') != '' && $session->get('type') == 1)
	{
	        //if user is login then it will be redirect to login page    			
	   return $this->redirect($this->generateUrl('drp_dashboard'));
	}
	$em = $this->getDoctrine()->getEntityManager();
	$repository = $em->getRepository('DRPAdminBundle:User');
	if ($request->getMethod() == 'POST')
        {
		$session->clear();
	    	$userName = $request->get('username');
	    	$password = md5($request->get('password'));
	    	
		//find email, password type and status of admin
                $user = $repository->findOneBy(array('username' => $userName, 'password' => $password,'type'=>1,'status'=>1 ));
		$userEmail = $repository->findOneBy(array('email' => $userName, 'password' => $password,'type'=>1,'status'=>1 ));
         	if ($user) 
         	{
              	
			//set session of admin login                        
		        $session->set('userId', $user->getId());
			 $session->set('number', $number);
			 $session->set('type', 1);
			$session->set('name', $user->getFirstName());
			$session->set('picture', $user->getPicture()); 

			

			//echo "<pre>";print_r($session->get('picture'));die;            
		        return $this->redirect($this->generateUrl('drp_dashboard'));
         	} 

		if ($userEmail) 
         	{
              	
			//set session of User login                        
		        $session->set('userId', $userEmail->getId());
			$session->set('name', $userEmail->getFirstName());
			$session->set('picture', $userEmail->getPicture()); 
			 $session->set('type', 1);
			//echo "<pre>";print_r($session->get('picture'));die;            
		        return $this->redirect($this->generateUrl('drp_dashboard'));
         	} 

        	else 
         	{
			
                	return $this->render('DRPAdminBundle:Pages:login.html.twig', array('name' => 'Invalid Email/Password'));
         	}

        	
		
	}    
		return $this->render('DRPAdminBundle:Pages:login.html.twig');
     }
    /*===End function for admin login======*/

     /*===Start function for admin logout======*/	
     public function logoutAction(Request $request)
     {
	$session = $this->getRequest()->getSession();

	
    	//$session->remove('userId');
	$session->clear();
    	return $this->redirect($this->generateUrl('drp_adminLogin'));
     }	
     /*===End function for admin logout======*/		


    /*===Start function for dashboard======*/	
    public function dashboardAction()
    {
	$session = $this->getRequest()->getSession();
	if($session->get('type') != 1)
	{
	        //if user is login then it will be redirect to login page    			
	 return $this->redirect($this->generateUrl('drp_adminLogin'));
	}

	 $em = $this->getDoctrine()
	 ->getEntityManager();
	  $totalUsers = $em->createQueryBuilder()
   	 ->select('count(c.id) AS totalUsers')
   	 ->from('DRPAdminBundle:User',  'c')
    	->Where('c.type = 4')
    	->getQuery()
    	->getResult();
	 $em = $this->getDoctrine()
	 ->getEntityManager();
	  $totalCompanies = $em->createQueryBuilder()
   	 ->select('count(c.id) AS totalCompanies')
   	 ->from('DRPAdminBundle:Business',  'c')
    	->getQuery()
    	->getResult();	
	$totalPlans = $em->createQueryBuilder()
   	 ->select('count(c.id) AS totalPlans')
   	 ->from('DRPAdminBundle:Plan',  'c')
    
    	->getQuery()
    	->getResult();	


	$totalDRB = $em->createQueryBuilder()
   	 ->select('count(drb.id) AS totalDRB,drb.modification_datetime')
   	 ->from('DRPAdminBundle:Book',  'drb')
	->leftJoin('DRPAdminBundle:RegistrationType', 'reg', "WITH", "reg.code=drb.registration_type")
	
	->where('reg.property_type=:type')
	->groupBy('drb.modification_datetime')
	->setParameter('type','drb')    
    	->getQuery()
    	->getResult();	

	$response = new Response(json_encode(array('totalDRB' => $totalDRB)));
	$response->headers->set('Content-Type', 'application/json');

//return $response;



	//echo"<pre>";print_r($totalDRB);die;

	 $dashboardDetails = array('totalCompanies' => $totalCompanies[0]['totalCompanies'],'totalUsers' => $totalUsers[0]['totalUsers'],'totalPlans' => $totalPlans[0]['totalPlans'],'totalDRB'=>$totalDRB);

	

	//echo"<pre>";print_r($dashboardDetails);die;


	$currentDate = date('Y-m-d');
	//$weekStartDate = date('Y-m-d', strtotime(' -7 day '));
	$currentMonth = date('Y-m');
	$currentYear = date('Y');
	$totalSearchesByUsers = array();
	//echo"<pre>";print_r($_POST);die;
	
	
	$totalSearchesByUsersToday = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentDate.'%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','SEARCH')    
    	->getQuery()
    	->getArrayResult();	

	$totalSearchesByUsersWeek = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentMonth.'%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','SEARCH')   
    	->getQuery()
    	->getArrayResult();

	$totalSearchesByUsersMonth = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentMonth.'%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','SEARCH')    
    	->getQuery()
    	->getArrayResult();

	$totalSearchesByUsersYear = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentYear.'-%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','SEARCH')  
	->getQuery()
    	->getArrayResult();

	
	
	$totalSearchesByAdminYear = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByAdmin')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentYear.'-%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','ADMIN_SEARCH')   
 
    	->getQuery()
    	->getArrayResult();


	$totalSearchesByAdminMonth = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByAdmin')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentMonth.'-%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','ADMIN_SEARCH')   
 
    	->getQuery()
    	->getArrayResult();

	$totalSearchesByAdminToday = $em->createQueryBuilder()
   	->select('count(SearchHistory.id) AS totalSearchesByAdmin')
   	->from('DRPAdminBundle:Log',  'SearchHistory')
	
	->where('SearchHistory.last_updated like :last_updated')
	->setParameter('last_updated',$currentDate.'%')   

	->andwhere('SearchHistory.event = :event')
	->setParameter('event','ADMIN_SEARCH')   
 
    	->getQuery()
    	->getArrayResult();


	$totalAmountYear = $em->createQueryBuilder()
   	->select('Plan')
   	->from('DRPAdminBundle:UserPlan',  'payment')
	->leftJoin('DRPAdminBundle:User', 'User', "WITH", "User.id=payment.user_id")
	->leftJoin('DRPAdminBundle:Plan', 'Plan', "WITH", "Plan.id=payment.plan_id")
	->where('payment.activation_datetime like :activation_datetime')
	->setParameter('activation_datetime',$currentYear.'-%')   
	->andwhere('payment.status = 1')
    	->getQuery()
    	->getArrayResult();
	
	$year=0;
	foreach($totalAmountYear as $amount)
	{

		$year = $year+$amount['price'];
		
		
	}


	$totalAmountToday = $em->createQueryBuilder()
   	->select('Plan')
   	->from('DRPAdminBundle:UserPlan',  'payment')
	->leftJoin('DRPAdminBundle:User', 'User', "WITH", "User.id=payment.user_id")
	->leftJoin('DRPAdminBundle:Plan', 'Plan', "WITH", "Plan.id=payment.plan_id")
	->where('payment.activation_datetime like :activation_datetime')
	->setParameter('activation_datetime',$currentDate.'%')   
	->andwhere('payment.status = 1')
    	->getQuery()
    	->getArrayResult();


	$today=0;
	foreach($totalAmountToday as $amountToday)
	{

		$today = $today+$amountToday['price'];
		
		
	}

	


	$totalAmountMonth = $em->createQueryBuilder()
   	->select('Plan')
   	->from('DRPAdminBundle:UserPlan',  'payment')
	->leftJoin('DRPAdminBundle:User', 'User', "WITH", "User.id=payment.user_id")
	->leftJoin('DRPAdminBundle:Plan', 'Plan', "WITH", "Plan.id=payment.plan_id")
	->where('payment.activation_datetime like :activation_datetime')
	->setParameter('activation_datetime',$currentMonth.'-%')   
	->andwhere('payment.status = 1')
    	->getQuery()
    	->getArrayResult();


	$month=0;
	foreach($totalAmountMonth as $amountMonth)
	{

		$month = $month+$amountMonth['price'];
		
		
	}
	


	
	
	


	



	


        return $this->render('DRPAdminBundle:Pages:dashboard.html.twig',array('dashboardDetails'=>$dashboardDetails, 'totalSearchesByUsersToday'=>$totalSearchesByUsersToday[0]['totalSearchesByUsers'], 'totalSearchesByUsersWeek'=>$totalSearchesByUsersWeek[0]['totalSearchesByUsers'], 'totalSearchesByUsersMonth'=>$totalSearchesByUsersMonth[0]['totalSearchesByUsers'], 'totalSearchesByUsersYear'=>$totalSearchesByUsersYear[0]['totalSearchesByUsers'],'totalSearchesByAdminYear'=>$totalSearchesByAdminYear[0]['totalSearchesByAdmin'],'totalSearchesByAdminMonth'=>$totalSearchesByAdminMonth[0]['totalSearchesByAdmin'],'totalSearchesByAdminToday'=>$totalSearchesByAdminToday[0]['totalSearchesByAdmin'],'year'=>$year,'month'=>$month,'today'=>$today));
    }

    /*===End function for dashboard======*/

 	public function chartsAction()

	{
		$em = $this->getDoctrine() ->getEntityManager();
		$totalDRB = $em->createQueryBuilder()
	   	 ->select('count(drb.id) AS totalDRB,drb.modification_datetime')
	   	 ->from('DRPAdminBundle:Book',  'drb')
		->leftJoin('DRPAdminBundle:RegistrationType', 'reg', "WITH", "reg.code=drb.registration_type")
	
		->where('reg.property_type=:type')
		->groupBy('drb.modification_datetime')
		->setParameter('type','drb')    
	    	->getQuery()
	    	->getResult();	

		$response = new Response(json_encode(array('totalDRB' => $totalDRB)));
		$response->headers->set('Content-Type', 'application/json');
		return $response;

	}

	public function getUsersSearchesAction()
	{
		$em = $this->getDoctrine() ->getEntityManager();
		$currentDate = date('Y-m-d');
		//$weekStartDate = date('Y-m-d', strtotime(' -7 day '));
		$currentMonth = date('Y-m');
		$currentYear = date('Y');
		$totalSearchesByUsers = array();
		//echo"<pre>";print_r($_POST);die;
		if( isset($_POST['period']) && $_POST['period'] == 'Today' )
		{
			
			$totalSearchesByUsers = $em->createQueryBuilder()
		   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
		   	->from('DRPAdminBundle:SearchHistory',  'SearchHistory')
			->where('SearchHistory.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime',$currentDate.' %')    
		    	->getQuery()
		    	->getArrayResult();	

		}

		if( isset($_POST['period']) && $_POST['period'] == 'Week' )
		{
			$totalSearchesByUsers = $em->createQueryBuilder()
		   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
		   	->from('DRPAdminBundle:SearchHistory',  'SearchHistory')
			->where('SearchHistory.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime',$currentDate.'%')    
		    	->getQuery()
		    	->getArrayResult();
		}
		if( isset($_POST['period']) && $_POST['period'] == 'Month' )
		{
			$totalSearchesByUsers = $em->createQueryBuilder()
		   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
		   	->from('DRPAdminBundle:SearchHistory',  'SearchHistory')
			->where('SearchHistory.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime', $currentMonth.'-%')    
		    	->getQuery()
		    	->getArrayResult();
		}
		if( isset($_POST['period']) && $_POST['period'] == 'Year' )
		{
			$totalSearchesByUsers = $em->createQueryBuilder()
		   	->select('count(SearchHistory.id) AS totalSearchesByUsers')
		   	->from('DRPAdminBundle:SearchHistory',  'SearchHistory')
			->where('SearchHistory.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime',$currentYear.'-%')    
		    	->getQuery()
		    	->getArrayResult();
		}
			//echo"<pre>";print_r($totalSearchesByUsers);die;
		return new response($totalSearchesByUsers[0]['totalSearchesByUsers']);

		
	}
	





     /*===Start function for forgot password======*/	
     public function forgotPasswordAction(Request $request)
     {
	
	$email = $_POST['email'];

	$em = $this->getDoctrine() ->getEntityManager();
    	$repository = $em->getRepository('DRPAdminBundle:User');
    	
           $user = $repository->findOneBy(array('email' => $email,'type'=>1));
           if ($user) 
           {   
		//genrate a random number
		$newPassword=$this->generateRandomString(8);
		//echo $newPassword;
		$encPass=md5($newPassword);
		$forgotPassword = $em->createQueryBuilder()
		->select('fPassword')
		->update('DRPAdminBundle:User',  'fPassword')
		->set('fPassword.password', ':password')
		->setParameter('password', $encPass)
		->where('fPassword.email=:email')
		->setParameter('email', $email)
		->getQuery()
		->getResult();
		$date=date("Y/m/d.");
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		$headers .= 'From: <support@rdrp.com>' . "\r\n";
		$to = $email;
		$subject = " Admin Password Reset";

		$txt='

<table width="100%" border="0" cellspacing="0" cellpadding="0" style="min-width: 320px;"><tr><td align="center" bgcolor="#eff3f8" >


<!--[if gte mso 10]>
<table width="680" border="0" cellspacing="0" cellpadding="0" >
<tr><td>
<![endif]-->

<table border="0" cellspacing="0" cellpadding="0" class="table_width_100" width="100%" style="max-width: 680px; min-width: 300px;" >
	
	<!--header -->
	<tr><td align="center" >
		<!-- padding -->
		<table width="90%" border="0" cellspacing="0" cellpadding="0" style="margin: 30px 0 31px;">
			
	<!--header END-->

	<!--content 1 -->
	<tr><td align="center" bgcolor="#fbfcfd">
		<table width="90%" border="0" cellspacing="0" cellpadding="0">
			<tr><td align="left" valign="top" class="mob_center">
									<a href="#" target="_blank" style="color: #596167; font-family: Arial, Helvetica, sans-serif; font-size: 13px;">
									<font face="Arial, Helvetica, sans-seri; font-size: 13px;" size="3" color="#596167">
									<img src="http://bwcmultimedia.com/PS/drp/images/logo.png" width="115" height="85px" alt="DRP" border="0" style="display: block;" /></font></a>
								</td></tr>

			<tr><td align="center">
				
				<div style="line-height: 44px;">
					<font face="Arial, Helvetica, sans-serif" size="5" color="#57697e" style="font-size: 34px;">
					<span style="font-family: Arial, Helvetica, sans-serif; font-size: 34px; color: #57697e;">
						Admin Password Reset
					</span></font>
				</div>
				<!-- padding -->
			</td></tr>
			<tr><td align="center">
				<div style="line-height: 24px;">
					<font face="Arial, Helvetica, sans-serif" size="4" color="#57697e" style="font-size: 15px;">
					<span style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; color: #57697e;">
						Hello '. $user->getFirstName().' '. $user->getLastName().' you have successfuly reset your password <br> Your new password is: <b>'.$newPassword.'</b> .
					</span></font>
				</div>
				<!-- padding --><div style="height: 40px; line-height: 40px; font-size: 10px;">&nbsp;</div>
			</td></tr>
			<tr><td align="center">
				<div style="line-height: 24px;">
					<a href="#" target="_blank" style="color: #596167; font-family: Arial, Helvetica, sans-serif; font-size: 13px;">
						<font face="Arial, Helvetica, sans-seri; font-size: 13px;" size="3" color="#596167">
							 <a href="http://bwcmultimedia.com/PS/drp/" style="background-color: #3598dc;
    color: #ffffff; font-size: 17px;
    outline: medium none !important;
    padding: 13px 42px;
    text-decoration: none;" >Click Here For Login</a></font></a>
				</div>
				<!-- padding --><div style="height: 60px; line-height: 60px; font-size: 10px;">&nbsp;</div>
			</td></tr>
		</table>		
	</td></tr>
	<!--content 1 END-->

	<!--content 2 -->
	
</table>
<!--[if gte mso 10]>
</td></tr>
</table>
<![endif]-->
 
</td></tr>
</table>
			
';
	

		//$txt='Hello '. $user->getFirstName().' '. $user->getLastName().',<br><br>Your password has been reset on '.$date.'<br><br>Your new Password is: <b>'.	$newPassword.'</b>';
		mail($to,$subject,$txt,$headers);	


		return new response('SUCCESS');	
          }							               
	 	return new response('FAILURE');	
	 }
	/*===End function for forgot password======*/

	/*===Start function for reset password message======*/
	public function resetPasswordAction(Request $request)
	{
		 return $this->render('DRPAdminBundle:Pages:passwordSuccess.html.twig');	

	}
	/*===End function for reset password message======*/

	/*===Start function for show all users======*/
	public function showUsersAction(Request $request)
	{
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		$users = $em->createQueryBuilder()
		->select('User, UserPlan.token,Business.name')
		->from('DRPAdminBundle:User',  'User')
		->leftJoin('DRPAdminBundle:UserPlan', 'UserPlan', "WITH", "UserPlan.user_id=User.id")
		->leftJoin('DRPAdminBundle:Business', 'Business', "WITH", "Business.user_id=User.id")

		->where('User.type=:type')
		->setParameter('type', 4)
		->addOrderBy('User.id','DESC')
		->getQuery()
		->getArrayResult();
	
		//echo"<pre>";print_r($users);die;
		
		foreach($users as $user)
		{
			$arrUser[$user[0]['id']] = $user[0];
			$arrUser[$user[0]['id']]['token'] = $user['token'];
			$arrUser[$user[0]['id']]['name'] = $user['name'];

		}

		//echo"<pre>";print_r($arrUser);die;
		//$html = '';
		
		//$html =  $this->render('DRPAdminBundle:Pages:users.html.twig',array('users'=>$users));
			return $this->render('DRPAdminBundle:Pages:users.html.twig',array('users'=>$arrUser));
		
		

	}

	


	/*===End function for show all users======*/

	/*===Start function for delete user======*/
	public function deleteUserAction(Request $request,$id)
	{
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of user
		$user = $em->getRepository('DRPAdminBundle:User')->find($id);
		// delete the data of user
		$em->remove($user);
		$em->flush();

		$session = $this->getRequest()->getSession(); 
		$ipAddress = $_SERVER['REMOTE_ADDR'];
		$params['event'] = $this->getLogEventTitleAction('DELETE_USER');
		$params['description'] = $this->getLogEventDescriptionAction('DELETE_USER');
		$params['userId'] =$session->get('userId');
		$params['ipAddress'] = $ipAddress;
		$params['creatorId'] = $session->get('userId');
		$this->setLogAction($params);
			

		return $this->redirect($this->generateUrl('drp_users'));
		

	}
	/*===End function for delete user======*/

	/*===Start function for edit user======*/
	public function editUserAction(Request $request,$id)
	{
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}	
		$em = $this->getDoctrine()->getEntityManager();
		$editUser = $em->getRepository('DRPAdminBundle:User')->find($id);
		$editCompany = $em->getRepository('DRPAdminBundle:Business')->findOneBy(array('user_id'=>$id));
		//echo"<pre>";print_r($editCompany);die;

		$editPlan = $em->getRepository('DRPAdminBundle:UserPlan')->findOneBy(array('user_id'=>$id));
		//echo"<pre>";print_r($editPlan);die;

		$businessType = $em->createQueryBuilder()
		->select('business')
		->from('DRPAdminBundle:BusinessType',  'business')
		->getQuery()
		->getResult();

		$plans = $em->createQueryBuilder()
		->select('plan')
		->from('DRPAdminBundle:Plan',  'plan')
		->getQuery()
		->getResult();

		if($request->getMethod() == 'POST') 
		{
			$sourcePath = $file = $_FILES['images']['name'];
			 $file1  = $_FILES['images']['tmp_name'];  
    			move_uploaded_file($_FILES["images"]["tmp_name"],
      			"uploads/user/" . $_FILES["images"]["name"]);

			$company = $file = $_FILES['companyImage']['name'];
			 $file2  = $_FILES['companyImage']['tmp_name'];  
    			move_uploaded_file($_FILES["companyImage"]["tmp_name"],
      			"uploads/company/" . $_FILES["companyImage"]["name"]);


			$firstName = $request->get('firstname');
			$hidImage = $request->get('hidImage');
			$hidCompanyImage = $request->get('hidCompanyImage');
			$middleName = $request->get('middlename');
			$lastName = $request->get('lastname');
			$email = $request->get('email');
			$userName = $request->get('username');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$passcode = $request->get('passcode');
			$type = $request->get('type');

			$name = $request->get('name');
			$description=$request->get('description');
			$emailCompany = $request->get('emailCompany');
			$address = $request->get('address');
			$tinCompany = $request->get('tinCompany');
			$telephone1Company = $request->get('tel1Company');
			$telephone2Company = $request->get('tel2Company');
			
			$planId= $request->get('plan');


			$editUser->setFirstName($firstName);
			$editUser->setMiddleName($middleName);
			$editUser->setLastName($lastName);
			$editUser->setEmail($email);
			$editUser->setUsername($userName);
			$editUser->setTelephone1($telephone1);
			$editUser->setTelephone2($telephone2);
			if($sourcePath == "")
			{
				$editUser->setPicture($hidImage);
			}
			else
			{

				$editUser->setPicture($sourcePath);
			}
			
			$em->persist($editUser);
			$em->flush();
	


			$editCompany->setName($name);
			$editCompany->setDescription($description);
			$editCompany->setEmail($emailCompany);
			$editCompany->setAddress($address);
			$editCompany->setTin($tinCompany);
			if($company == "")
			{
				$editCompany->setPicture($hidCompanyImage);
			}
			else
			{
				$editCompany->setPicture($company);
			}
			
			
			$editCompany->setTelephone1($telephone1Company);
			$editCompany->setTelephone2($telephone2Company);
			
			$em->persist($editCompany);
			$em->flush();	

			
			$editPlan->setPlanId($planId);
			$em->persist($editPlan);
			$em->flush();	
			
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			$params['event'] = $this->getLogEventTitleAction('EDIT_USER');
			$params['description'] = $this->getLogEventDescriptionAction('EDIT_USER');
			$params['userId'] =$session->get('userId');
			$params['ipAddress'] = $ipAddress;
			$params['creatorId'] = $session->get('userId');
			$this->setLogAction($params);
			

			return $this->redirect($this->generateUrl('drp_users'));
		}
		return $this->render('DRPAdminBundle:Pages:editUser.html.twig',array('editUser'=>$editUser,'businessType'=>$businessType,'plans'=>$plans,'editCompany'=>$editCompany)); 
		

	}
	/*===End function for edit user======*/

	/*===Start function for add user======*/
	public function addUserAction(Request $request)
	{

		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		$plans = $em->createQueryBuilder()
		->select('plan')
		->from('DRPAdminBundle:Plan',  'plan')
		->getQuery()
		->getResult();
		$businessType = $em->createQueryBuilder()
		->select('businsess')
		->from('DRPAdminBundle:BusinessType',  'businsess')
		->getQuery()
		->getResult();
		//echo"<pre>";print_r($businessType);die;



		if($request->getMethod() == 'POST') 
		{

			$sourcePath = $file = $_FILES['image']['name'];
			 $file1  = $_FILES['image']['tmp_name'];  
    			move_uploaded_file($_FILES["image"]["tmp_name"],
      			"uploads/user/" . $_FILES["image"]["name"]);

			$company = $file = $_FILES['companyImage']['name'];

			$token= $this->generateRandomString(6);

			 $file2  = $_FILES['companyImage']['tmp_name'];  
    			move_uploaded_file($_FILES["companyImage"]["tmp_name"],
      			"uploads/company/" . $_FILES["companyImage"]["name"]);

			$firstName = $request->get('firstname');
			$password=$request->get('password');
			$middleName = $request->get('middlename');
			$lastName = $request->get('lastname');
			$email = $request->get('email');
			$userName = $request->get('username');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$nin = $request->get('nin');
			$tin = $request->get('tin');
			$type = $request->get('business');
			$token= $this->generateRandomString(8);
			

			$name = $request->get('name');
			$description=$request->get('description');
			$emailCompany = $request->get('emailCompany');
			$address = $request->get('address');
			$tinCompany = $request->get('tinCompany');
			$telephone1Company = $request->get('tel1Company');
			$telephone2Company = $request->get('tel2Company');
			
			$planId= $request->get('plan');

			$addUser = new User();
			$addUser->setFirstName($firstName);
			$addUser->setMiddleName($middleName);
			$addUser->setLastName($lastName);
			$addUser->setEmail($email);
			$addUser->setUsername($userName);
			$addUser->setTelephone1($telephone1);
			$addUser->setTelephone2($telephone2);
			$addUser->setPassword(md5($password));
			$addUser->setNin($nin);
			$addUser->setTin($tin);
			$addUser->setStatus(1);
			$addUser->setType(4);
			$addUser->setPicture($sourcePath);
			$addUser->setToken($token);
			$addUser->setCreatorId($session->get('userId'));
			$em->persist($addUser);
			$em->flush();

			$userId = $addUser->getId(); 
			
			$addBusiness = new Business();
			$addBusiness->setName($name);
			$addBusiness->setDescription($description);
			$addBusiness->setEmail($emailCompany);
			$addBusiness->setType($type);
			$addBusiness->setAddress($address);
			$addBusiness->setPicture($company);
			$addBusiness->setTin($tinCompany);
			$addBusiness->setTelephone1($telephone1Company);
			$addBusiness->setTelephone2($telephone2Company);
			$addBusiness->setUserId($userId);
			$addUser->setCreatorId($session->get('userId'));
			$em->persist($addBusiness);
			$em->flush();	

			$plan = new UserPlan();
			$plan->setPlanId($planId);
			$plan->setUserId($userId);
			$plan->setToken($token);
			$plan->setStatus(0);
			$em->persist($plan);
			$em->flush();	



			

			$date=date("Y/m/d.");
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= 'From: <support@rdrp.com>' . "\r\n";
			$to = $email;
			$subject = "User Registration";
			$txt='Hello '. $firstName.' '. $lastName.',<br><br>Your have created account on '.$date.'<br><br>Email is: <b>'.	$email.'</b>'.'and your password is'. $password;
			mail($to,$subject,$txt,$headers);	
		//echo $txt;die;
			
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			$params['event'] = $this->getLogEventTitleAction('ADD_USER');
			$params['description'] = $this->getLogEventDescriptionAction('ADD_USER');
			$params['userId'] =$session->get('userId');
			$params['ipAddress'] = $ipAddress;
			$params['creatorId'] = $session->get('userId');
			$this->setLogAction($params);




			return $this->redirect($this->generateUrl('drp_users'));
		}
		return $this->render('DRPAdminBundle:Pages:addUser.html.twig',array('plans'=>$plans,'businessType'=>$businessType)); 
		

	}
	/*===End function for add user======*/

	/*===Start function for  Check Email ======*/
	function checkEmailExistance($emailId)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}

		$em = $this->getDoctrine()->getEntityManager();
		$emailExistanceCheck = $em->createQueryBuilder()
		->select('checkEmail')
		->from('DRPAdminBundle:User',  'checkEmail')
		->where('checkEmail.email=:email')
		->setParameter('email', $emailId)
		->getQuery()
		->getArrayResult();

		//echo $emailId."<pre>";print_r($emailExistanceCheck);die;
		
		return count($emailExistanceCheck);
		
	}
	/*===End function for  Check Email ======*/
	function checkEmailAction()
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}

		$checkEmail  = $this->checkEmailExistance($_POST['email']);

		if($checkEmail > 0)
		{
			return new response('SUCCESS');	
		}
		
		return new response('FAILURE');	
	}
	


	
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
	
	/*===Start function for list companies======*/
	public function showBusinessAction(Request $request)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		$business = $em->createQueryBuilder()
		->select('business')
		->from('DRPAdminBundle:Business',  'business')
		//->where('business.type=:type')
		//->setParameter('type', 1)
		->getQuery()
		->getResult();
		return $this->render('DRPAdminBundle:Pages:business.html.twig',array('business'=>$business));

	}
	/*===End function for list companies======*/

	/*===Start function for add New company======*/
	public function addCompanyAction(Request $request)
	{

		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		if($request->getMethod() == 'POST') 
		{
			$name = $request->get('name');
			$description=$request->get('description');
			$email = $request->get('email');
			$address = $request->get('address');
			$tin = $request->get('tin');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$addBusiness = new Business();
			$addBusiness->setName($name);
			$addBusiness->setDescription($description);
			$addBusiness->setEmail($email);
			$addBusiness->setAddress($address);
			$addBusiness->setTin($tin);
			$addBusiness->setTelephone1($telephone1);
			$addBusiness->setTelephone2($telephone2);
			$em->persist($addBusiness);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_showBusiness'));
		}
			return $this->render('DRPAdminBundle:Pages:addCompany.html.twig');
	}	
	/*===End function for add New company======*/	


	/*===Start function for edit company======*/
	public function editCompanyAction(Request $request,$id)
	{

		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}

		$em = $this->getDoctrine()->getEntityManager();
		$business = $em->getRepository('DRPAdminBundle:Business')->find($id);
		if($request->getMethod() == 'POST') 
		{
			$name = $request->get('name');
			$description=$request->get('description');
			$email = $request->get('email');
			$address = $request->get('address');
			$tin = $request->get('tin');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
		
			$business->setName($name);
			$business->setDescription($description);
			$business->setEmail($email);
			$business->setAddress($address);
			$business->setTin($tin);
			$business->setTelephone1($telephone1);
			$business->setTelephone2($telephone2);
			$em->persist($business);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_showBusiness'));
		}
			return $this->render('DRPAdminBundle:Pages:editCompany.html.twig',array('business'=>$business));
	}	
	/*===End function for edit company======*/	
	
	/*===Start function for delete company======*/
	public function deleteCompanyAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}


		$em = $this->getDoctrine()->getEntityManager();
		// find the id of company
		$user = $em->getRepository('DRPAdminBundle:Business')->find($id);
		// delete the data of company
		$em->remove($user);
		$em->flush();
		return $this->redirect($this->generateUrl('drp_showBusiness'));
		

	}
	/*===End function for delete company======*/

		public function changeStatusAction()
		{
		$statusHtml = '';
		$em = $this->getDoctrine()->getEntityManager();
		
		if( isset($_POST['currentStatus']) && $_POST['currentStatus'] == 0 )
		{
			$status = 1;
			$statusString = '<span class="label label-sm label-info">Active</span>';
		}
		else
		{
			$status = 0;
			$statusString = '<span class="label label-sm label-danger">Inactive</span>';
		}
			
		$id = $_POST['id'];
		
		if( isset($_POST['objectType']) && $_POST['objectType'] == 'Company' )
		{
			$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('Business')
			->update('DRPAdminBundle:Business',  'Business')
			->set('Business.status', ':status')
			->setParameter('status', $status)
			->where('Business.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();
		}

		if(isset($_POST['objectType']) && $_POST['objectType'] == 'Plan')
		{
			$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('plan')
			->update('DRPAdminBundle:Plan',  'plan')
			->set('plan.status', ':plan')
			->setParameter('plan', $status)
			->where('plan.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();
			
			


		}
		if(isset($_POST['objectType']) && $_POST['objectType'] == 'User')
		{
			$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('user')
			->update('DRPAdminBundle:User',  'user')
			->set('user.status', ':user')
			->setParameter('user', $status)
			->where('user.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();


		}
		if(isset($_POST['objectType']) && $_POST['objectType'] == 'Instrument')
		{
			$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('instrument')
			->update('DRPAdminBundle:GlobalInstrument',  'instrument')
			->set('instrument.status', ':instrument')
			->setParameter('instrument', $status)
			->where('instrument.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();


		}
		if(isset($_POST['objectType']) && $_POST['objectType'] == 'BusinessType')
		{
			$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('business')
			->update('DRPAdminBundle:BusinessType',  'business')
			->set('business.status', ':business')
			->setParameter('business', $status)
			->where('business.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();


		}
		if(isset($_POST['objectType']) && $_POST['objectType'] == 'RType')
		{
		
			$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('reg')
			->update('DRPAdminBundle:RegistrationType',  'reg')
			->set('reg.status', ':reg')
			->setParameter('reg', $status)
			->where('reg.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();


		}
		if(isset($_POST['objectType']) && $_POST['objectType'] == 'Payment')
		{
			if( isset($_POST['currentStatus']) && $_POST['currentStatus'] == 0 )
			{
				$status = 1;
				$statusString = '<span class="label label-sm label-info">Received</span>';
			}
			else
			{
				$status = 0;
				$statusString = 'Pending';
			}
			$em = $this->getDoctrine()->getEntityManager();
			$UserPlan = $em->createQueryBuilder()
			->select('UserPlan')
			->from('DRPAdminBundle:UserPlan',  'UserPlan')
			->where('UserPlan.id=:id')
			->setParameter('id', $id)
			->getQuery()
			->getArrayResult();	
			
			/*======Increase Searches=========*/
			$planDetail = $em->createQueryBuilder()
			->select('plan')
			->from('DRPAdminBundle:Plan',  'plan')
			->where('plan.id=:id')
			->setParameter('id', $UserPlan[0]['plan_id'])
			->getQuery()
			->getArrayResult();
			

			$userDetail = $em->createQueryBuilder()
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			->where('user.id=:id')
			->setParameter('id', $UserPlan[0]['user_id'])
			->getQuery()
			->getArrayResult();
		

			$newSearches = $userDetail[0]['search_count_total'] + $planDetail[0]['searches'];

			$remainingSearches = $userDetail[0]['search_count_balance'] + $planDetail[0]['searches'];
			

		
			
			/*======Increase Searches=========*/			


			$disablePrevious = $em->createQueryBuilder() 
			->select('userPlan')
			->update('DRPAdminBundle:UserPlan',  'userPlan')
			->set('userPlan.status', ':status')
			->where('userPlan.user_id = :id')
			->setParameter('status', 0)
			->setParameter('id', $UserPlan[0]['user_id'])
			->getQuery()
			->getResult();

			

			$payment = $em->createQueryBuilder() 
			->select('UserPlan')
			->update('DRPAdminBundle:UserPlan',  'UserPlan')
			->set('UserPlan.payment_status', ':UserPlan')
			->setParameter('UserPlan', $status)
			->set('UserPlan.status', ':status')
			->setParameter('status', $status)
			->where('UserPlan.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();

				
			$renewPlan = $em->createQueryBuilder() 
				->select('renewPlan')
				->update('DRPAdminBundle:User',  'renewPlan')
				->set('renewPlan.search_count_total', ':total')
				->where('renewPlan.id = :id')
				->setParameter('total', $newSearches)
				->setParameter('id', $UserPlan[0]['user_id'])
				->getQuery()
				->getResult();

			$updateBalanced = $em->createQueryBuilder() 
				->select('renewPlan')
				->update('DRPAdminBundle:User',  'renewPlan')
				->set('renewPlan.search_count_balance', ':total')
				->where('renewPlan.id = :id')
				->setParameter('total', $remainingSearches)
				->setParameter('id', $UserPlan[0]['user_id'])
				->getQuery()
				->getResult();
		




		}



		
		$statusHtml.='<a id="status-'.$id.'" class="" title="Click to Change" onclick="javascript:changeStatus(\'status-'.$id.'\','.$status.');">'.$statusString.'</a>'; 
		
		return new response($statusHtml);				
	}
	/*------------------------------- End : Function to change Status User ------------------------------*/

	
	/*===Start function for show plans======*/
	public function showPlansAction(Request $request)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}

		$em = $this->getDoctrine()->getEntityManager();
		$plans = $em->createQueryBuilder()
		->select('plan')
		->from('DRPAdminBundle:Plan',  'plan')
		->getQuery()
		->getResult();
		return $this->render('DRPAdminBundle:Pages:plans.html.twig',array('plans'=>$plans));
	
	}
	/*===End function for show plans======*/
	
	/*===Start function for edit plans======*/
	public function editPlanAction(Request $request ,$id)
	{

		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}	
		
		$em = $this->getDoctrine()->getEntityManager();
		$editPlan = $em->getRepository('DRPAdminBundle:Plan')->find($id);
		if($request->getMethod() == 'POST') 
		{
			$name = $request->get('name');
			$description = $request->get('description');
			$price = $request->get('price');
			$searches = $request->get('searches');
			$editPlan->setName($name);
			$editPlan->setDescription($description);
			$editPlan->setPrice($price);
			$editPlan->setSearches($searches);	
			$em->persist($editPlan);
			$em->flush();
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			$params['event'] = $this->getLogEventTitleAction('EDIT_PLAN');
			$params['description'] = $this->getLogEventDescriptionAction('EDIT_PLAN');
			$params['userId'] =$session->get('userId');
			$params['ipAddress'] = $ipAddress;
			$params['creatorId'] = $session->get('userId');
			$this->setLogAction($params);
			
			return $this->redirect($this->generateUrl('drp_showPlans'));
			
		}
		return $this->render('DRPAdminBundle:Pages:editPlan.html.twig',array('editPlan'=>$editPlan));
	
	}
	/*===End function for edit plans======*/

	/*===Start function for add plan======*/
	public function addPlanAction(Request $request)
	{	

		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		if($request->getMethod() == 'POST') 
		{
			$name = $request->get('name');
			$description = $request->get('description');
			$searches = $request->get('searches');
			$price = $request->get('price');
			$plan = new Plan();
			$plan->setName($name);
			$plan->setDescription($description);
			$plan->setPrice($price);
			$plan->setStatus(1);
			$plan->setSearches($searches);
			$em->persist($plan);
			$em->flush();

			$ipAddress = $_SERVER['REMOTE_ADDR'];
			$params['event'] = $this->getLogEventTitleAction('ADD_PLAN');
			$params['description'] = $this->getLogEventDescriptionAction('ADD_PLAN');
			$params['userId'] =$session->get('userId');
			$params['ipAddress'] = $ipAddress;
			$params['creatorId'] = $session->get('userId');
			$this->setLogAction($params);

			return $this->redirect($this->generateUrl('drp_showPlans'));
			
		}
		return $this->render('DRPAdminBundle:Pages:addPlan.html.twig');
	
	}
	/*===End function for add plan======*/

	/*===Start function for delete plan======*/
	public function deletePlanAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of plan
		$plan = $em->getRepository('DRPAdminBundle:Plan')->find($id);
		// delete the data of plan
		$em->remove($plan);
		$em->flush();
		$ipAddress = $_SERVER['REMOTE_ADDR'];
			$params['event'] = $this->getLogEventTitleAction('DELETE_PLAN');
			$params['description'] = $this->getLogEventDescriptionAction('DELETE_PLAN');
			$params['userId'] =$session->get('userId');
			$params['ipAddress'] = $ipAddress;
			$params['creatorId'] = $session->get('userId');
			$this->setLogAction($params);

		return $this->redirect($this->generateUrl('drp_showPlans'));

	}
	/*===Start function for delete plan======*/

	/*===Start function for view profile of user======*/
	public function userProfileAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of user
		$userProfile = $em->getRepository('DRPAdminBundle:User')->find($id);
		$company = $em->getRepository('DRPAdminBundle:Business')->findOneBy(array('user_id'=>$id));

		$plans = $em->createQueryBuilder()
		->select('plan.name,plan.price,plan.description,plan.searches')
		->from('DRPAdminBundle:Plan',  'plan')
		->leftJoin('DRPAdminBundle:UserPlan', 'user', "WITH", "user.plan_id=plan.id")
		->where('user.user_id = :id')
		->setParameter('id', $id)
		->addorderBy('user.id','DESC')
		->SetMaxResults(1)
		
		->getQuery()
		->getResult();

		$userPlan = $em->createQueryBuilder()
		->select('plan.name,plan.price,plan.description,plan.searches,user.status,user.token,user.payment_status,user.id')
		->from('DRPAdminBundle:Plan',  'plan')
		->leftJoin('DRPAdminBundle:UserPlan', 'user', "WITH", "user.plan_id=plan.id")
		->where('user.user_id = :id')
		->setParameter('id', $id)
		->getQuery()
		->getResult();
		
		//echo"<pre>";print_r($userPlan);die;
		return $this->render('DRPAdminBundle:Pages:userProfile.html.twig',array('userProfile'=>$userProfile,'company'=>$company,'plans'=>$plans,'userPlan'=>$userPlan));

	}
	/*===End function for view profile of user======*/

	/*===Start function for view company======*/
	public function viewCompanyAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of user
		$viewCompany = $em->getRepository('DRPAdminBundle:Business')->find($id);
		return $this->render('DRPAdminBundle:Pages:viewCompany.html.twig',array('viewCompany'=>$viewCompany));

	}
	/*===End function for view company======*/

	/*===Start function for view plan======*/
	public function viewPlanAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of user
		$viewPlan = $em->getRepository('DRPAdminBundle:Plan')->find($id);
		return $this->render('DRPAdminBundle:Pages:viewPlan.html.twig',array('viewPlan'=>$viewPlan));

	}
	/*===End function for view plan======*/

	/*===Start function for show profile of admin======*/
	public function adminProfileAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		$viewProfile = $em->getRepository('DRPAdminBundle:User')->find($id);


		$currentDate = date('Y-m-d');
		//$weekStartDate = date('Y-m-d', strtotime(' -7 day '));
		$currentMonth = date('Y-m');
		$currentYear = date('Y');
		$totalSearchesByUsers = array();
		//echo"<pre>";print_r($_POST);die;
	
	
	

			$leaseToday = $em->createQueryBuilder()
			->select('count(lease.id) AS totalLeaseToday')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentDate.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_LEASE') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();

			$leaseMonth = $em->createQueryBuilder()
			->select('count(lease.id) AS totalLeaseMonth')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentMonth.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_LEASE') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();


			



			$leaseYear = $em->createQueryBuilder()
			->select('count(lease.id) AS totalLeaseYear')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentYear.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_LEASE') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();
		





			$drbToday = $em->createQueryBuilder()
			->select('count(lease.id) AS totalDRBToday')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentDate.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_DRB') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();


			$drbMonth = $em->createQueryBuilder()
			->select('count(lease.id) AS totalDRBMonth')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentMonth.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_DRB') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();


			$drbYear = $em->createQueryBuilder()
			->select('count(lease.id) AS totalDRBYear')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentYear.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_DRB') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();


			
			$userYear = $em->createQueryBuilder()
			->select('count(lease.id) AS totalUserYear')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentYear.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_USER') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();

			$userMonth = $em->createQueryBuilder()
			->select('count(lease.id) AS totalUserMonth')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentMonth.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_USER') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();


			$userToday = $em->createQueryBuilder()
			->select('count(lease.id) AS totalUserToday')
			->from('DRPAdminBundle:Log',  'lease')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('lease.last_updated like :last_updated')
			->setParameter('last_updated',$currentDate.'%')   
			->andwhere('lease.event = :mainEvent')
			->setParameter('mainEvent','ADD_USER') 
			->andwhere('lease.user_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->getQuery()
			->getResult();


			$userDetailToday =  $em->createQueryBuilder()
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('user.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime',$currentDate.'%')   
			->andwhere('user.creator_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->orderBy('user.id','DESC')
			 ->setMaxResults(5)
			->getQuery()
			->getResult();


			
			$userDetailYear = $em->createQueryBuilder()
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('user.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime',$currentYear.'%')   
			->andwhere('user.creator_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->orderBy('user.id','DESC')
			 ->setMaxResults(5)
			->getQuery()
			->getResult();



			$userDetailMonth = $em->createQueryBuilder()
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=lease.registration_type")
			->where('user.creation_datetime like :creation_datetime')
			->setParameter('creation_datetime',$currentMonth.'%')   
			->andwhere('user.creator_id = :userId')
			->setParameter('userId',$session->get('userId'))
			->orderBy('user.id','DESC')
			 ->setMaxResults(5)
			->getQuery()
			->getResult();


			
			


		return $this->render('DRPAdminBundle:Pages:adminProfile.html.twig',array('viewProfile'=>$viewProfile,'drbToday'=>$drbToday[0]['totalDRBToday'],'drbMonth'=>$drbMonth[0]['totalDRBMonth'],'drbYear'=>$drbYear[0]['totalDRBYear'],'leaseToday'=>$leaseToday[0]['totalLeaseToday'],'leaseMonth'=>$leaseMonth[0]['totalLeaseMonth'],'leaseYear'=>$leaseYear[0]['totalLeaseYear'],'userToday'=>$userToday[0]['totalUserToday'],'userMonth'=>$userMonth[0]['totalUserMonth'],'userYear'=>$userYear[0]['totalUserYear'],'userDetailMonth'=>$userDetailMonth,'userDetailYear'=>$userDetailYear,'userDetailToday'=>$userDetailToday));
	}
	/*===End function for show profile of admin======*/

	/*===Start function for admin settings======*/
	public function adminSettingsAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$em = $this->getDoctrine()->getEntityManager();
		$userInfo = $em->getRepository('DRPAdminBundle:User')->find($id);
	
		return $this->render('DRPAdminBundle:Pages:adminSettings.html.twig',array('userInfo'=>$userInfo));
	}
	/*===End function for admin settings======*/

	/*===Start function for update admin Info======*/
	public function updateAdminInfoAction(Request $request)
	{

		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$session = $this->getRequest()->getSession(); 		
		$userId = $session->get('userId');			
		$firstName = $_POST['firstname'];
		$lastName = $_POST['lastname'];
		$email = $_POST['email'];
		//$userName = $_POST['username'];
		$passcode = $_POST['passcode'];
		//$telephone1 = $_POST['telephone1'];
		//$telephone2 = $_POST['telephone2'];
		//$id = $_POST['id'];
		$em = $this->getDoctrine()->getEntityManager();
		$updateInfo = $em->createQueryBuilder() 
		->select('user')
		->update('DRPAdminBundle:User',  'user')
		->set('user.first_name', ':fname')
		->setParameter('fname', $firstName)
		->set('user.last_name', ':lname')
		->setParameter('lname', $lastName)
		->set('user.email', ':email')
		->setParameter('email', $email)
		//->set('user.username', ':username')
		//->setParameter('username', $userName)
		->set('user.passcode', ':passcode')
		->setParameter('passcode', $passcode)
		//->set('user.telephone1', ':telephone1')
		//->setParameter('telephone1', $telephone1)
		//->set('user.telephone2', ':telephone2')
		//->setParameter('telephone2', $telephone2)
		->where('user.id = :id')
		->setParameter('id', $userId)
		->getQuery()
		->getResult();
		$session->remove('name');
		$session->set('name', $firstName); 
		return new response('SUCCESS');	
		
	}
	/*===End function for update admin Info======*/
	
	public function updateAdminImageAction(Request $request)
	{
		

		
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}
		$adminId = $session->get('userId');	
		$sourcePath = $file = $_FILES['file']['name'];
		 $file1  = $_FILES['file']['tmp_name'];  
    		move_uploaded_file($_FILES["file"]["tmp_name"],
      		"uploads/user/" . $_FILES["file"]["name"]);
		$em = $this->getDoctrine()->getEntityManager();
			$confirmedSubscribe = $em->createQueryBuilder() 
			->select('admin')
			->update('DRPAdminBundle:User',  'admin')
			->set('admin.picture', ':image')
			->setParameter('image', $sourcePath)
			->where('admin.id = :id')
			->setParameter('id', $adminId)
			->getQuery()
			->getResult();
			$session->remove('picture');
			$session->set('picture', $sourcePath); 

			return new response('SUCCESS');		
		
		
	}	
	
	public function changePasswordAction()
		{
			
		$session = $this->getRequest()->getSession();
		if($session->get('type') != 1)
		{
			//if user is login then it will be redirect to login page    			
		 return $this->redirect($this->generateUrl('drp_adminLogin'));
		}		
		    	$userId = $session->get('userId');		
			//echo $userId."<PRE>";print_r($_POST);die;
			$em = $this->getDoctrine()->getEntityManager();
			$salonCurrentPassword = $em->createQueryBuilder() 
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			->where('user.id = :id')
			->setParameter('id', $userId)
			->andwhere('user.type = :type')
			->setParameter('type', 1)
			->andwhere('user.status = :status')
			->setParameter('status', 1)
			->getQuery()
			->getResult();
			
			$currentPassword = $salonCurrentPassword[0]->password ;  //  echo "<PRE>";print_r($currentPassword);die;
			$oldPassword = $_POST["oldPassword"];     
			$newPassword = $_POST["newPassword"];     
			$repeatPassword = $_POST["repeatPassword"];     
			//echo $newPassword."----".$repeatPassword;die;
			$em = $this->getDoctrine()->getEntityManager();
			
			if( ($currentPassword == md5($oldPassword)) && ($newPassword == $repeatPassword) )
			{
				$queryUpdatePassword = $em->createQueryBuilder() 
				->select('user')
				->update('DRPAdminBundle:User',  'user')
				->set('user.password', ':password')
				->setParameter('password', md5($newPassword))
				->where('user.id = :id')
				->setParameter('id', $userId)
				->getQuery()
				->getResult();
				
				// echo "<PRE>";print_r($newPasswords);die;
				return new response("SUCCESS");
			}
			else
			{
				if( $currentPassword != md5($oldPassword) )
				{
					return new response("OLD_MISMATCH");
				}
				else
				{
					return new response("NEW_MISMATCH");
				}
			}
		}

		/*===Start function for manage Payments======*/
		public function managePaymentsAction(Request $request)
		{
			
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$payments = $em->createQueryBuilder()
			->select('user.first_name,user.last_name,user.middle_name,plan.name,UserPlan.status,UserPlan.id,UserPlan.token,UserPlan.payment_status')
			->from('DRPAdminBundle:UserPlan',  'UserPlan')
			->leftJoin('DRPAdminBundle:User', 'user', "WITH", "user.id=UserPlan.user_id")
			->leftJoin('DRPAdminBundle:Plan', 'plan', "WITH", "UserPlan.plan_id=plan.id")
			
			
			//->where('UserPlan.payment_status = :paymentStatus')
			//->setParameter('paymentStatus', 0)
			->addOrderBy('UserPlan.payment_status', 'ASC')
			->getQuery()
			->getResult();
			//echo"<pre>";print_r($payments);die;
			return $this->render('DRPAdminBundle:Pages:managePayments.html.twig',array('payments'=>$payments));

		}
		/*===End function for manage Payments======*/

		/*===Start function for manage logs======*/
		public function manageLogsAction(Request $request)
		{
			
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}

			$em = $this->getDoctrine()->getEntityManager();
			$logs = $em->createQueryBuilder()
			->select('logs.event,logs.description,logs.id,user.first_name,user.last_name,logs.user_id,logs.last_updated')
			->from('DRPAdminBundle:Log',  'logs')
			->leftJoin('DRPAdminBundle:User', 'user', "WITH", "user.id=logs.user_id")
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
			//echo"<pre>";print_r($logs);die;
			
			return $this->render('DRPAdminBundle:Pages:manageLogs.html.twig',array('logs'=>$logs));

		}
		/*===End function for manage logs======*/

		/*===Start function for delete logs======*/
		public function deleteLogsAction(Request $request,$id)
		{
			
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$logs = $em->getRepository('DRPAdminBundle:Log')->find($id);
			$em->remove($logs);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_manageLogs'));

		}
		/*===End function for delete logs======*/
		


		/*===Start function for search Quota======*/
		public function searchQuotaAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			
			$em = $this->getDoctrine()->getEntityManager();
			$searchQuota = $em->createQueryBuilder()
			->select('searchQuota')
			->from('DRPAdminBundle:User',  'searchQuota')
			->where('searchQuota.type=:type')
			->setParameter('type', 4)
			->getQuery()
			->getResult();
			//echo"<pre>";print_r($searchQuota);die;
			

			return $this->render('DRPAdminBundle:Pages:searchQuota.html.twig',array('searchQuota'=>$searchQuota));

		}
		/*===End function for search Quota======*/

		/*===Start function for instrument type======*/
		public function instrumentTypeAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$instrument = $em->createQueryBuilder()
			->select('instrument')
			->from('DRPAdminBundle:GlobalInstrument',  'instrument')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();

			return $this->render('DRPAdminBundle:Pages:instrumentType.html.twig',array('instrument'=>$instrument));

		}
		/*===End function for instrument type======*/

		/*===Start function for edit instrument type======*/
		public function editInstrumentAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$editInstrument = $em->getRepository('DRPAdminBundle:GlobalInstrument')->find($id);
			if($request->getMethod() == 'POST') 
			{
				$type = $request->get('type');
				$description = $request->get('description');
				$price = $request->get('price');
				$editInstrument->setType($type);
				$editInstrument->setDescription($description);
				$em->persist($editInstrument);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_instrumentType'));
			}

			return $this->render('DRPAdminBundle:Pages:editInstrument.html.twig',array('editInstrument'=>$editInstrument));

		}
		/*===End function for edit instrument type======*/

		/*===Start function for add instrument type======*/
		public function addInstrumentAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			
			if($request->getMethod() == 'POST') 
			{
				$type = $request->get('type');
				$description = $request->get('description');
				$addInstrument = new GlobalInstrument();
				$addInstrument->setType($type);
				$addInstrument->setDescription($description);
				$em->persist($addInstrument);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_instrumentType'));
			}

			return $this->render('DRPAdminBundle:Pages:addInstrument.html.twig');

		}
		/*===End function for add instrument type======*/



		/*===Start function for delete instrument type======*/
		public function deleteInstrumentAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$instrument = $em->getRepository('DRPAdminBundle:GlobalInstrument')->find($id);
			$em->remove($instrument);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_instrumentType'));

		}
		/*===End function for delete instrument type======*/




		/*===Start function for business type======*/
		public function businessTypeAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$business = $em->createQueryBuilder()
			->select('business')
			->from('DRPAdminBundle:BusinessType',  'business')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();

			return $this->render('DRPAdminBundle:Pages:businessType.html.twig',array('business'=>$business));

		}
		/*===End function for business type======*/


		/*===Start function for delete Business type======*/
		public function deleteBusinessTypeAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$business = $em->getRepository('DRPAdminBundle:BusinessType')->find($id);
			$em->remove($business);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_instrumentType'));

		}
		/*===End function for delete Business type======*/


		/*===Start function for edit Business type======*/
		public function editBusinessTypeAction(Request $request,$id)
		{



			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$editBusinessType = $em->getRepository('DRPAdminBundle:BusinessType')->find($id);
			if($request->getMethod() == 'POST') 
			{
				$type = $request->get('type');
				$description = $request->get('description');
				$editBusinessType->setType($type);
				$editBusinessType->setDescription($description);
				$em->persist($editBusinessType);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_businessType'));
			}

			return $this->render('DRPAdminBundle:Pages:editBusinessType.html.twig',array('editBusinessType'=>$editBusinessType));

		}	
		/*===End function for edit Business type======*/

		/*===Start function for add Business type======*/
		public function addBusinessTypeAction(Request $request)
		{	
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			if($request->getMethod() == 'POST') 
			{
				$type = $request->get('type');
				$description = $request->get('description');
				$addBusinessType = new BusinessType();
				$addBusinessType->setType($type);
				$addBusinessType->setDescription($description);
				$em->persist($addBusinessType);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_businessType'));
			}

			return $this->render('DRPAdminBundle:Pages:addBusinessType.html.twig');

		}	
		/*===End function for add Business type======*/

		/*===Start function for registration type======*/
		public function registrationTypeAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:RegistrationType',  'reg')
			->where('reg.property_type=:type')
			->setParameter('type', 'drb')
			->getQuery()
			->getResult();
			return $this->render('DRPAdminBundle:Pages:registrationType.html.twig',array('registrationType'=>$registrationType));

		}
		/*===End function for registration type======*/

		/*===Start function for edit Registration type======*/
		public function editRegistrationTypeAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$editRegistrationType = $em->getRepository('DRPAdminBundle:RegistrationType')->find($id);
			if($request->getMethod() == 'POST') 
			{
				$code = $request->get('code');	
				$type = $request->get('type');
				$description = $request->get('description');
				$editRegistrationType->setType($type);
				$editRegistrationType->setDescription($description);
				$editRegistrationType->setCode($code);
				$editRegistrationType->setPropertyType('drb');
				$em->persist($editRegistrationType);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_registrationType'));
			}

			return $this->render('DRPAdminBundle:Pages:editRegistrationType.html.twig',array('editRegistrationType'=>$editRegistrationType));

		}	
		/*===End function for edit Registration type======*/

		/*===Start function for add Registration type======*/
		public function addRegistrationTypeAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			if($request->getMethod() == 'POST') 
			{
				$type = $request->get('type');

				$code = $request->get('code');

				$description = $request->get('description');
				$addRegistrationType = new RegistrationType();
				$addRegistrationType->setType($type);
				$addRegistrationType->setDescription($description);
				$addRegistrationType->setCode($code);
				$addRegistrationType->setPropertyType('drb');
				$em->persist($addRegistrationType);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_registrationType'));
			}

			return $this->render('DRPAdminBundle:Pages:addRegistrationType.html.twig');

		}	


		/*===End function for add Registration type======*/

		public function editLeaseRegistrationTypeAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$editRegistrationType = $em->getRepository('DRPAdminBundle:RegistrationType')->find($id);
			if($request->getMethod() == 'POST') 
			{
				$type = $request->get('type');
				$code = $request->get('code');
				$description = $request->get('description');
				$editRegistrationType->setType($type);
				$editRegistrationType->setDescription($description);
				$editRegistrationType->setPropertyType('lease');
				$editRegistrationType->setCode($code);
				$em->persist($editRegistrationType);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_leaseRegistrationType'));
			}

			return $this->render('DRPAdminBundle:Pages:editLeaseRegistrationType.html.twig',array('editRegistrationType'=>$editRegistrationType));

		}	
		/*===End function for edit Registration type======*/

		/*===Start function for add Registration type======*/
		public function addLeaseRegistrationTypeAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			if($request->getMethod() == 'POST') 
			{
				$code = $request->get('code');	
				$type = $request->get('type');
				$description = $request->get('description');
				$addRegistrationType = new RegistrationType();
				$addRegistrationType->setType($type);
				$addRegistrationType->setDescription($description);
				$addRegistrationType->setCode($code);
				$addRegistrationType->setPropertyType('lease');
				$em->persist($addRegistrationType);
				$em->flush();
				return $this->redirect($this->generateUrl('drp_leaseRegistrationType'));
			}

			return $this->render('DRPAdminBundle:Pages:addLeaseRegistrationType.html.twig');

		}	




		/*===Start function for delete Registration type======*/
		public function deleteRegistrationTypeAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$registration = $em->getRepository('DRPAdminBundle:RegistrationType')->find($id);
			$em->remove($registration);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_registrationType'));

		}
		/*===End function for delete Registration type======*/


		/*===Start function for delete Registration type======*/
		public function deleteLeaseRegistrationTypeAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$registration = $em->getRepository('DRPAdminBundle:RegistrationType')->find($id);
			$em->remove($registration);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_leaseRegistrationType'));

		}
		/*===End function for delete Registration type======*/


		/*===Start function for  Registration ======*/
		public function registrationAction(Request $request)
		{	

			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();

			if($request->getMethod() == 'POST') 
			{
				$sourcePath = $file = $_FILES['image']['name'];
				$file1  = $_FILES['image']['tmp_name'];  
		    		move_uploaded_file($_FILES["image"]["tmp_name"],
		      		"uploads/user/" . $_FILES["image"]["name"]);
				$firstName = $request->get('firstname');
				$password=$request->get('password');
				$middleName = $request->get('middlename');
				$lastName = $request->get('lastname');
				$email = $request->get('email');
				$userName = $request->get('username');
				$telephone1 = $request->get('tel1');
				$telephone2 = $request->get('tel2');
				$nin = $request->get('nin');
				$tin = $request->get('tin');
				$passcode = $request->get('passcode');

				$emailExistanceCheck = $this->checkEmailExistance($request->get('email'));
				//echo $emailExistanceCheck;die;
				if( $emailExistanceCheck == 0 )
				{

					$token= $this->generateRandomString(6);
					$addUser = new User();
					$addUser->setFirstName($firstName);
					$addUser->setMiddleName($middleName);
					$addUser->setLastName($lastName);
					$addUser->setEmail($email);
					$addUser->setUsername($userName);
					$addUser->setTelephone1($telephone1);
					$addUser->setTelephone2($telephone2);
					$addUser->setPassword(md5($password));
					$addUser->setNin($nin);
					$addUser->setTin($tin);
					$addUser->setPicture($sourcePath);	
					$addUser->setToken($token);
					$addUser->setPasscode($passcode);
					$addUser->setType(4);
					$addUser->setStatus(0);
					$addUser->setCreatorId(0);
					$em->persist($addUser);
					$em->flush();
					$userId = $addUser->getId();
					$date=date("Y/m/d.");
					$headers = "MIME-Version: 1.0" . "\r\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
					$headers .= 'From: <support@rdrp.com>' . "\r\n";
					$to = $email;
					$subject = "User Registration";
					$txt='Hello '. $firstName.' '. $lastName.',<br><br>Your have created account on '.$date.'<br><br>Email is: <b>'.	$email.'</b>'.'and your password is'.$password;
					mail($to,$subject,$txt,$headers);
					$registrationDetail = $em->getRepository('DRPAdminBundle:User')->find($userId);

					return $this->render('DRPAdminBundle:Pages:registrationSuccess.html.twig',array('registrationDetail'=>$registrationDetail));
				}
				else
				{
					return $this->redirect($this->generateUrl('drp_login'));
				}
				
			}
			return $this->redirect($this->generateUrl('drp_login'));
		}
		/*===End function for  Registration ======*/

		
		public function setLogAction($param)
		{
			
			$em = $this->getDoctrine()->getEntityManager();
			$event = $param['event'];
			$description = $param['description'];
			$userId = $param['userId'];
			$ipAddress = $param['ipAddress'];
			$creatorId = $param['creatorId'];

			$insertLog = new Log();
			$insertLog->setEvent($event);
			$insertLog->setDescription($description);
			$insertLog->setUserId($userId);
			$insertLog->setIpAddress($ipAddress);
			$insertLog->setCreatorId($creatorId);
			$em->persist($insertLog);
			$em->flush();
			return true;
		}
		public function getLogAction($param)
		{


		}

		public function getLogEventTitleAction($param)
		{
			if( $param == 'ADD_USER' )			
				return 'ADD_USER';
			else if( $param == 'REGISTRATION_FAILURE' )			
				return 'REGISTRATION_FAILURE';
			else if( $param == 'LOGIN_SUCCESS' )			
				return 'LOGIN_SUCCESS';
			else if( $param == 'LOGIN_FAILURE' )			
				return 'LOGIN_FAILURE';
			else if( $param == 'LOGOUT_SUCCESS' )			
				return 'LOGOUT_SUCCESS';

			else if( $param == 'EDIT_USER' )			
				return 'EDIT_USER';
			else if( $param == 'DELETE_USER' )			
				return 'DELETE_USER';
			else if( $param == 'EDIT_PLAN' )			
				return 'EDIT_PLAN';
			else if( $param == 'ADD_PLAN' )			
				return 'ADD_PLAN';

			else if( $param == 'DELETE_PLAN' )			
				return 'DELETE_PLAN';


			else if( $param == 'EDIT_DRB' )			
				return 'EDIT_DRB';
			else if( $param == 'ADD_DRB' )			
				return 'ADD_DRB';

			else if( $param == 'DELETE_DRB' )			
				return 'DELETE_DRB';


			else if( $param == 'EDIT_LEASE' )			
				return 'EDIT_LEASE';
			else if( $param == 'ADD_LEASE' )			
				return 'ADD_LEASE';

			else if( $param == 'DELETE_LEASE' )			
				return 'DELETE_LEASE';
			else if( $param == 'DELETE_LEASE' )			
				return 'DELETE_LEASE';

			else if( $param == 'ADD_ADMIN' )			
				return 'ADD_ADMIN';

			else if( $param == 'EDIT_ADMIN' )			
				return 'EDIT_ADMIN';

			else if( $param == 'DELETE_ADMIN' )			
				return 'DELETE_ADMIN';

			else if( $param == 'ADD_REGISTRAR_GENERAL' )			
				return 'ADD_REGISTRAR_GENERAL';

			else if( $param == 'EDIT_REGISTRAR_GENERAL' )			
				return 'EDIT_REGISTRAR_GENERAL';

			else if( $param == 'DELETE_REGISTRAR_GENERAL' )			
				return 'DELETE_REGISTRAR_GENERAL';
			


			

						

		}

		public function getLogEventDescriptionAction($param)
		{
			if( $param == 'ADD_USER' )			
				return 'REGISTRATION_SUCCESS';
			else if( $param == 'REGISTRATION_FAILURE' )			
				return 'REGISTRATION_FAILURE';
			else if( $param == 'LOGIN_SUCCESS' )			
				return 'LOGIN_SUCCESS';
			else if( $param == 'LOGIN_FAILURE' )			
				return 'LOGIN_FAILURE';
			else if( $param == 'LOGOUT_SUCCESS' )			
				return 'LOGOUT_SUCCESS';
			else if( $param == 'EDIT_USER' )			
				return 'EDIT_USER';

			else if( $param == 'DELETE_USER' )			
				return 'DELETE_USER';
			else if( $param == 'EDIT_PLAN' )			
				return 'EDIT_PLAN';
			else if( $param == 'ADD_PLAN' )			
				return 'ADD_PLAN';

			else if( $param == 'DELETE_PLAN' )			
				return 'DELETE_PLAN';

			else if( $param == 'EDIT_DRB' )			
				return 'EDIT_DRB';
			else if( $param == 'ADD_DRB' )			
				return 'ADD_DRB';

			else if( $param == 'DELETE_DRB' )			
				return 'DELETE_DRB';


			else if( $param == 'EDIT_LEASE' )			
				return 'EDIT_LEASE';
			else if( $param == 'ADD_LEASE' )			
				return 'ADD_LEASE';

			else if( $param == 'DELETE_LEASE' )			
				return 'DELETE_LEASE';	

			else if( $param == 'ADD_ADMIN' )			
				return 'ADD_ADMIN';

			else if( $param == 'EDIT_ADMIN' )			
				return 'EDIT_ADMIN';

			else if( $param == 'DELETE_ADMIN' )			
				return 'DELETE_ADMIN';

			else if( $param == 'ADD_REGISTRAR_GENERAL' )			
				return 'ADD_REGISTRAR_GENERAL';

			else if( $param == 'EDIT_REGISTRAR_GENERAL' )			
				return 'EDIT_REGISTRAR_GENERAL';

			else if( $param == 'DELETE_REGISTRAR_GENERAL' )			
				return 'DELETE_REGISTRAR_GENERAL';
			

		




		}


		public function sendEmailAction($param)
		{


		}
		
		/*===Start function for Manage Properties======*/
		public function managePropertiesAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$properties = $em->createQueryBuilder()
			->select('properties.lomp,properties.lessor,properties.lessee,properties.grantor,properties. 	grantee,properties.stamp_duty,properties.registrar_general_initial,properties.registering_party,properties.or_number,properties.land_situation,properties.id,rtype.type')
			->from('DRPAdminBundle:Book',  'properties')
			->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			
			->getQuery()
			->getResult();
			//echo"<pre>";print_r($properties);die;
			return $this->render('DRPAdminBundle:Pages:manageProperties.html.twig',array('properties'=>$properties));

		}	
		/*===End function for Manage Properties======*/

		/*===Start function for Manage Properties======*/
		public function addDRBAction(Request $request)
		{
				$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
				
				$number = $session->get('number');	
				$code  = $_REQUEST['code'];
				$em = $this->getDoctrine()->getEntityManager();
				$lastTransaction = $em->createQueryBuilder()
				->select('book')
				->from('DRPAdminBundle:Book',  'book')
				->addOrderBy('book.id', 'DESC')
				  ->setMaxResults(1)
				//->where('business.type=:type')
				//->setParameter('type', 1)
				->getQuery()
				->getArrayResult();
			$getType = $em->getRepository('DRPAdminBundle:RegistrationType')->findOneBy(array('code'=>$code));

			$registrationType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:RegistrationType',  'reg')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
				
			
			$InstrumentType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:GlobalInstrument',  'reg')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();

			if($request->getMethod() == 'POST') 
			{
				
				$note = $request->get('notes');
				$registrationType = $request->get('rtype');
				$serialNumber = $request->get('serialNumber');
				$dateOfReciept=$request->get('drp');
				$situationOfLand = $request->get('sof');
				$dateOfExecution = $request->get('doe');
				$grantor = $request->get('grantor');
				$grantee = $request->get('grantee');
				$rir = $request->get('rir');
				$noi = $request->get('noi');
				$stampDuty = $request->get('sd');
				$orNumber = $request->get('or');
				$recipient = $request->get('recipient');
				$partyRegistring = $request->get('pr');
				$registrarGenralIntials = $request->get('rgi');
				$reciepientDate = $request->get('recipientDate');
				//echo $reciepientDate;die;

				/*$serialNumber = $request->get('sr');
				$lomp = $request->get('lomp');
				$lessor = $request->get('lessor');
				$lease = $request->get('lease');
	
				$rd = $request->get('rd');*/
				$company = $request->get('company');
				$firstName = $request->get('firstname');
				$middleName = $request->get('middlename');
				$lastName = $request->get('lastname');
				$nin = $request->get('nin');
				$dob = $request->get('dob');
				$address = $request->get('address');
				$email = $request->get('email');
				$telephone1 = $request->get('tel1');
				$telephone2 = $request->get('tel2');	

				$companyLs = $request->get('company1');
				$firstNameLs = $request->get('firstname1');
				$middleNameLs = $request->get('middlename1');
				$lastNameLs = $request->get('lastname1');
				$ninLs = $request->get('nin1');
				$dobLs = $request->get('dob1');
				$addressLs = $request->get('address1');
				$emailLs = $request->get('email1');
				$telephone1Ls = $request->get('tel11');
				$telephone2Ls = $request->get('tel21');


				

				$companyLs2 = $request->get('company2');
				$firstNameLs2 = $request->get('firstname2');
				$middleNameLs2 = $request->get('middlename2');
				$lastNameLs2 = $request->get('lastname2');
				$ninLs2 = $request->get('nin2');
				$dobLs2 = $request->get('dob2');
				$addressLs2 = $request->get('address2');
				$emailLs2 = $request->get('email2');
				$telephone1Ls2 = $request->get('tel112');
				$telephone2Ls2 = $request->get('tel212');
	


				$companyLe = $request->get('Lcompany');
				$firstNameLe = $request->get('Lfirstname');
				$middleNameLe = $request->get('Lmiddlename');
				$lastNameLe = $request->get('Llastname');
				$ninLe = $request->get('Lnin');
				$dobLe = $request->get('Ldob');
				$addressLe = $request->get('Laddress');
				$emailLe = $request->get('Lemail');
				$telephone1Le = $request->get('Ltel1');
				$telephone2Le = $request->get('Ltel2');	

				$companyLe1 = $request->get('Lcompany1');
				$firstNameLe1 = $request->get('Lfirstname1');
				$middleNameLe1 = $request->get('Lmiddlename1');
				$lastNameLe1 = $request->get('Llastname1');
				$ninLe1 = $request->get('Lnin1');
				$dobLe1 = $request->get('Ldob1');
				$addressLe1 = $request->get('Laddress1');
				$emailLe1 = $request->get('Lemail1');
				$telephone1Le1 = $request->get('Ltel11');
				$telephone2Le1 = $request->get('Ltel2');	


				$companyLe2 = $request->get('Lcompany2');
				$firstNameLe2 = $request->get('Lfirstname2');
				$middleNameLe2 = $request->get('Lmiddlename2');
				$lastNameLe2 = $request->get('Llastname2');
				$ninLe2 = $request->get('Lnin2');
				$dobLe2 = $request->get('Ldob2');
				$addressLe2 = $request->get('Laddress2');
				$emailLe2 = $request->get('Lemail2');
				$telephone1Le2 = $request->get('Ltel12');
				$telephone2Le2 = $request->get('Ltel22');	





				
				$companyPR = $request->get('companynamePR');
				$firstNamePR = $request->get('firstnamePR');
				$middleNamePR = $request->get('middlenamePR');
				$lastNamePR = $request->get('lastnamePR');
				$ninPR = $request->get('ninPR');
				$dobPR = $request->get('dobPR');

			//echo $dobPR ;die;

				$addressPR = $request->get('addressPR');
				$emailPR = $request->get('emailPR');
				$telephone1PR = $request->get('tel1PR');
				$telephone2PR = $request->get('tel2PR');

				$companyPR1 = $request->get('companyNamePR1');
				$firstNamePR1 = $request->get('firstNamePR1');
				$middleNamePR1 = $request->get('middleNamePR1');
				$lastNamePR1 = $request->get('lastNamePR1');
				$ninPR1 = $request->get('ninPR1');
				$dobPR1 = $request->get('dobPR1');
				$addressPR1 = $request->get('addressPR1');
				$emailPR1 = $request->get('emailPR1');
				$telephone1PR1 = $request->get('tel1PR1');
				$telephone2PR1 = $request->get('tel2PR1');

				$companyPR2 = $request->get('companyNamePR2');
				$firstNamePR2 = $request->get('firstNamePR2');
				$middleNamePR2 = $request->get('middleNamePR2');
				$lastNamePR2 = $request->get('lastNamePR2');
				$ninPR2 = $request->get('ninPR2');
				$dobPR2 = $request->get('dobPR2');
				$addressPR2 = $request->get('addressPR2');
				$emailPR2 = $request->get('emailPR2');
				$telephone1PR2 = $request->get('tel1PR2');
				$telephone2PR2 = $request->get('tel2PR2');



				$companyRP = $request->get('companynameRP');
				$firstNameRP = $request->get('firstnameRP');
				$middleNameRP = $request->get('middlenameRP');
				$lastNameRP = $request->get('lastnameRP');
				$ninRP = $request->get('ninRP');
				$dobRP = $request->get('dobRP');
				$addressRP = $request->get('addressRP');
				$emailRP = $request->get('emailRP');
				$telephone1RP = $request->get('tel1RP');
				$telephone2RP = $request->get('tel2RP');	

				

				
				$addProperty = new Book();
				$addProperty->setRegistrationType($code);
				$addProperty->setNote($note);
				$addProperty->setReceiptDate($dateOfReciept);
				$addProperty->setExecutionDate($dateOfExecution);
				//$addProperty->setLomp($lomp);
				//$addProperty->setLessor($lessor);
				$addProperty->setGrantor($grantor);
				$addProperty->setGrantee($grantee);
				$addProperty->setStampDuty($stampDuty);
				$addProperty->setOrNumber($orNumber);
				$addProperty->setRecipient($recipient);
				$addProperty->setInstrumentType($noi);
				$addProperty->setRecipientDate($reciepientDate);
				$addProperty->setRegistrarGeneralInitial($registrarGenralIntials);
				$addProperty->setLandSituation($situationOfLand);
				$addProperty->setSerialNumber($serialNumber);
			



				$newReferenceNumber =  $request->get('hidRegNumber');	

//echo $newReferenceNumber;die;
				$addProperty->setReferenceNumber($newReferenceNumber);
				$addProperty->setReferenceInRegister($rir);
				$addProperty->setRegisteringParty($partyRegistring);
				


				$em->persist($addProperty);
				$em->flush();
				$bookId = $addProperty->getId();

				$registrationStatus = new RegistrationStatus();
				$registrationStatus->setBookId($bookId);
				$registrationStatus->setStatus(0);
				$registrationStatus->setPropertyType('drb');
				$em->persist($registrationStatus);
				$em->flush();

				

				$addLessor = new Company();
				$addLessor->setCompanyName($company);
				$addLessor->setFirstName($firstName);
				$addLessor->setMiddleName($middleName);
				$addLessor->setLastName($lastName);
				$addLessor->setNin($nin);
				$addLessor->setTelephone1($telephone1);
				$addLessor->setTelephone2($telephone2);
				$addLessor->setDob($dob);
				$addLessor->setAddress($address);
				$addLessor->setType('GR');
				$addLessor->setEmail($email);
				$addLessor->setBookId($bookId);
				$em->persist($addLessor);
				$em->flush();

				if($firstNameLs||$firstNameLs!="")
				{

					$addGrantor1 = new Company();
					$addGrantor1->setCompanyName($companyLs);
					$addGrantor1->setFirstName($firstNameLs);
					$addGrantor1->setMiddleName($middleNameLs);
					$addGrantor1->setLastName($lastNameLs);
					$addGrantor1->setNin($ninLs);
					$addGrantor1->setTelephone1($telephone1Ls);
					$addGrantor1->setTelephone2($telephone2Ls);
					$addGrantor1->setDob($dobLs);
					$addGrantor1->setAddress($addressLs);
					$addGrantor1->setType('GR');
					$addGrantor1->setEmail($emailLs);
					$addGrantor1->setBookId($bookId);
					$em->persist($addGrantor1);
					$em->flush();


					$addGrantor2 = new Company();
					$addGrantor2->setCompanyName($companyLs2);
					$addGrantor2->setFirstName($firstNameLs2);
					$addGrantor2->setMiddleName($middleNameLs2);
					$addGrantor2->setLastName($lastNameLs2);
					$addGrantor2->setNin($ninLs2);
					$addGrantor2->setTelephone1($telephone1Ls2);
					$addGrantor2->setTelephone2($telephone2Ls2);
					$addGrantor2->setDob($dobLs2);
					$addGrantor2->setAddress($addressLs2);
					$addGrantor2->setType('GR');
					$addGrantor2->setEmail($emailLs2);
					$addGrantor2->setBookId($bookId);
					$em->persist($addGrantor2);
					$em->flush();
				}
				

				$addNewLease = new Company();
				$addNewLease->setCompanyName($companyLe);
				$addNewLease->setFirstName($firstNameLe);
				$addNewLease->setMiddleName($middleNameLe);
				$addNewLease->setLastName($lastNameLe);
				$addNewLease->setNin($ninLe);
				$addNewLease->setTelephone1($telephone1Le);
				$addNewLease->setTelephone2($telephone2Le);
				$addNewLease->setDob($dobLe);
				$addNewLease->setAddress($addressLe);
				$addNewLease->setEmail($emailLe);
				$addNewLease->setBookId($bookId);
				$addNewLease->setType('GE');
				$em->persist($addNewLease);
				$em->flush();


				if($firstNameLe1||$firstNameLe2!="")
				{
					$addNewGrantee1 = new Company();
					$addNewGrantee1->setCompanyName($companyLe1);
					$addNewGrantee1->setFirstName($firstNameLe1);
					$addNewGrantee1->setMiddleName($middleNameLe1);
					$addNewGrantee1->setLastName($lastNameLe1);
					$addNewGrantee1->setNin($ninLe1);
					$addNewGrantee1->setTelephone1($telephone1Le1);
					$addNewGrantee1->setTelephone2($telephone2Le1);
					$addNewGrantee1->setDob($dobLe1);
					$addNewGrantee1->setAddress($addressLe1);
					$addNewGrantee1->setEmail($emailLe1);
					$addNewGrantee1->setBookId($bookId);
					$addNewGrantee1->setType('GE');
					$em->persist($addNewGrantee1);
					$em->flush();
				



					$addNewGrantee2 = new Company();
					$addNewGrantee2->setCompanyName($companyLe2);
					$addNewGrantee2->setFirstName($firstNameLe2);
					$addNewGrantee2->setMiddleName($middleNameLe2);
					$addNewGrantee2->setLastName($lastNameLe2);
					$addNewGrantee2->setNin($ninLe2);
					$addNewGrantee2->setTelephone1($telephone1Le2);
					$addNewGrantee2->setTelephone2($telephone2Le2);
					$addNewGrantee2->setDob($dobLe2);
					$addNewGrantee2->setAddress($addressLe2);
					$addNewGrantee2->setEmail($emailLe2);
					$addNewGrantee2->setBookId($bookId);
					$addNewGrantee2->setType('GE');
					$em->persist($addNewGrantee2);
					$em->flush();

			}


				$addPartyRegistring = new Company();
				$addPartyRegistring->setCompanyName($companyPR);
				$addPartyRegistring->setFirstName($firstNamePR);
				$addPartyRegistring->setMiddleName($middleNamePR);
				$addPartyRegistring->setLastName($lastNamePR);
				$addPartyRegistring->setNin($ninPR);
				$addPartyRegistring->setTelephone1($telephone1PR);
				$addPartyRegistring->setTelephone2($telephone2PR);
				$addPartyRegistring->setDob($dobPR);
				$addPartyRegistring->setAddress($addressPR);
				$addPartyRegistring->setEmail($emailPR);
				$addPartyRegistring->setBookId($bookId);
				$addPartyRegistring->setType('PR');
				$em->persist($addPartyRegistring);
				$em->flush();

				if($firstNamePR1||$firstNamePR2!="")
				{

					$addPartyRegistring1 = new Company();
					$addPartyRegistring1->setCompanyName($companyPR1);
					$addPartyRegistring1->setFirstName($firstNamePR1);
					$addPartyRegistring1->setMiddleName($middleNamePR1);
					$addPartyRegistring1->setLastName($lastNamePR1);
					$addPartyRegistring1->setNin($ninPR1);
					$addPartyRegistring1->setTelephone1($telephone1PR1);
					$addPartyRegistring1->setTelephone2($telephone2PR1);
					$addPartyRegistring1->setDob($dobPR1);
					$addPartyRegistring1->setAddress($addressPR1);
					$addPartyRegistring->setEmail($emailPR1);
					$addPartyRegistring1->setBookId($bookId);
					$addPartyRegistring1->setType('PR');
					$em->persist($addPartyRegistring1);
					$em->flush();


					$addPartyRegistring2 = new Company();
					$addPartyRegistring2->setCompanyName($companyPR2);
					$addPartyRegistring2->setFirstName($firstNamePR2);
					$addPartyRegistring2->setMiddleName($middleNamePR2);
					$addPartyRegistring2->setLastName($lastNamePR2);
					$addPartyRegistring2->setNin($ninPR2);
					$addPartyRegistring2->setTelephone1($telephone1PR2);
					$addPartyRegistring2->setTelephone2($telephone2PR2);
					$addPartyRegistring2->setDob($dobPR2);
					$addPartyRegistring2->setAddress($addressPR2);
					$addPartyRegistring2->setEmail($emailPR2);
					$addPartyRegistring2->setBookId($bookId);
					$addPartyRegistring2->setType('PR');
					$em->persist($addPartyRegistring2);
					$em->flush();
				}




				$addRecipient = new Company();
				$addRecipient->setFirstName($firstNameRP);
				$addRecipient->setCompanyName($companyRP);	
				$addRecipient->setMiddleName($middleNameRP);
				$addRecipient->setLastName($lastNameRP);
				$addRecipient->setNin($ninRP);
				$addRecipient->setTelephone1($telephone1RP);
				$addRecipient->setTelephone2($telephone2RP);
				$addRecipient->setDob($dobRP);
				$addRecipient->setAddress($addressRP);
				$addRecipient->setEmail($emailRP);
				$addRecipient->setType('RP');
				$addRecipient->setBookId($bookId);
				$em->persist($addRecipient);
				$em->flush();

				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('ADD_DRB');
				$params['description'] = $this->getLogEventDescriptionAction('ADD_DRB');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);
				
				return $this->redirect($this->generateUrl('drp_showpdrb',array('code'=>$code,'number'=>$number)));


			

		}


			return $this->render('DRPAdminBundle:Pages:addDrb.html.twig',array('registrationType'=>$registrationType,'instrumentType'=>$InstrumentType,'getType'=>$getType));

		}
	
		/*===End function for Manage Properties======*/
		public function viewInstrumentAction(Request $request,$id)
		{
			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->getRepository('DRPAdminBundle:GlobalInstrument')->find($id);
			return $this->render('DRPAdminBundle:Pages:viewInstrumentType.html.twig',array('registrationType'=>$registrationType));


		}

		public function viewBusinessTypeAction(Request $request,$id)
		{

			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->getRepository('DRPAdminBundle:BusinessType')->find($id);
			return $this->render('DRPAdminBundle:Pages:viewBusinessType.html.twig',array('registrationType'=>$registrationType));

		}

		public function viewDRBTypeAction(Request $request,$id)
		{	
			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->getRepository('DRPAdminBundle:RegistrationType')->find($id);
			
		return $this->render('DRPAdminBundle:Pages:viewDRBType.html.twig',array('registrationType'=>$registrationType));


		}


		public function viewLeaseTypeAction(Request $request,$id)
		{
			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->getRepository('DRPAdminBundle:RegistrationType')->find($id);
			
		return $this->render('DRPAdminBundle:Pages:viewLeaseType.html.twig',array('registrationType'=>$registrationType));	

		}


		/*===Start function for Edit DRB======*/
		public function editDRBAction(Request $request,$id)
		{
		
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$code  = $_REQUEST['code'];
			$number = $session->get('number');
			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:RegistrationType',  'reg')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
		
			//echo"<pre>";print_r($registrationType);die;
			
			$InstrumentType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:GlobalInstrument',  'reg')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
			$getType = $em->getRepository('DRPAdminBundle:RegistrationType')->findOneBy(array('code'=>$code));
			$editDRB = $em->getRepository('DRPAdminBundle:Book')->find($id);
			$lessorType = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'GR'));
			$leaseType = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'GE'));
			$partyRegistring = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'PR'));
		
			$editrecipient = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'RP'));
			$registrationStatus = $em->getRepository('DRPAdminBundle:RegistrationStatus')->findOneBy(array('book_id'=>$id));
			


			if($request->getMethod() == 'POST') 
			{
				$note = $request->get('notes');
				$serialNumber = $request->get('serialNumber');
				$dateOfReciept=$request->get('drp');
				$situationOfLand = $request->get('sof');
				$dateOfExecution = $request->get('doe');
				$grantor = $request->get('grantor');
				$grantee = $request->get('grantee');
				$rir = $request->get('rir');
				$noi = $request->get('noi');
				$stampDuty = $request->get('sd');
				$orNumber = $request->get('or');
				$recipient = $request->get('recipient');
				//$partyRegistring = $request->get('pr');
				$registrarGenralIntials = $request->get('rgi');
				$reciepientDate = $request->get('recipientDate');

				$company = $request->get('company');
				$firstName = $request->get('firstname');
				$middleName = $request->get('middlename');
				$lastName = $request->get('lastname');
				$nin = $request->get('nin');
				$dob = $request->get('dob');
				$address = $request->get('address');
				$email = $request->get('email');
				$telephone1 = $request->get('tel1');
				$telephone2 = $request->get('tel2');		


				$companyLe = $request->get('Lcompany');
				$firstNameLe = $request->get('Lfirstname');
				$middleNameLe = $request->get('Lmiddlename');
				$lastNameLe = $request->get('Llastname');
				$ninLe = $request->get('Lnin');
				$dobLe = $request->get('Ldob');
				$addressLe = $request->get('Laddress');
				$emailLe = $request->get('Lemail');
				$telephone1Le = $request->get('Ltel1');
				$telephone2Le = $request->get('Ltel2');	

				
				$companyPR = $request->get('companynamePR');
				$firstNamePR = $request->get('firstnamePR');
				$middleNamePR = $request->get('middlenamePR');
				$lastNamePR = $request->get('lastnamePR');
				$ninPR = $request->get('ninPR');
				$dobPR = $request->get('dobPR');
				$addressPR = $request->get('addressPR');
				$emailPR = $request->get('emailPR');
				$telephone1PR = $request->get('tel1PR');
				$telephone2PR = $request->get('tel2PR');

				$companyRP = $request->get('companynameRP');
				$firstNameRP = $request->get('firstnameRP');
				$middleNameRP = $request->get('middlenameRP');
				$lastNameRP = $request->get('lastnameRP');
				$ninRP = $request->get('ninRP');
				$dobRP = $request->get('dobRP');
				$addressRP = $request->get('addressRP');
				$emailRP = $request->get('emailRP');
				$telephone1RP = $request->get('tel1RP');
				$telephone2RP = $request->get('tel2RP');	


				//echo $reciepientDate;die;

				/*$serialNumber = $request->get('sr');
				$lomp = $request->get('lomp');
				$lessor = $request->get('lessor');
				$lease = $request->get('lease');
	
				$rd = $request->get('rd');*/
				
				$editDRB->setNote($note);
				$editDRB->setRegistrationType($code);
				$editDRB->setReceiptDate($dateOfReciept);
				$editDRB->setExecutionDate($dateOfExecution);
				//$addProperty->setLomp($lomp);
				//$addProperty->setLessor($lessor);
				$editDRB->setGrantor($grantor);
				$editDRB->setGrantee($grantee);
				$editDRB->setStampDuty($stampDuty);
				$editDRB->setOrNumber($orNumber);
				$editDRB->setRecipient($recipient);
				$editDRB->setInstrumentType($noi);
				$editDRB->setRecipientDate($reciepientDate);
				$editDRB->setRegistrarGeneralInitial($registrarGenralIntials);
				$editDRB->setLandSituation($situationOfLand);
				$editDRB->setReferenceInRegister($rir);
				$editDRB->setSerialNumber($serialNumber);
				$em->persist($editDRB);
				$em->flush();



				
				$lessorType->setCompanyName($company);
				$lessorType->setFirstName($firstName);
				$lessorType->setMiddleName($middleName);
				$lessorType->setLastName($lastName);
				$lessorType->setNin($nin);
				$lessorType->setTelephone1($telephone1);
				$lessorType->setTelephone2($telephone2);
				$lessorType->setDob($dob);
				$lessorType->setAddress($address);
				
				$lessorType->setEmail($email);
				$em->persist($lessorType);
				$em->flush();

				
				$leaseType->setCompanyName($companyLe);
				$leaseType->setFirstName($firstNameLe);
				$leaseType->setMiddleName($middleNameLe);
				$leaseType->setLastName($lastNameLe);
				$leaseType->setNin($ninLe);
				$leaseType->setTelephone1($telephone1Le);
				$leaseType->setTelephone2($telephone2Le);
				$leaseType->setDob($dobLe);
				$leaseType->setAddress($addressLe);
				$leaseType->setEmail($emailLe);
				
				$em->persist($leaseType);
				$em->flush();

				
				$partyRegistring->setCompanyName($companyPR);
				$partyRegistring->setFirstName($firstNamePR);
				$partyRegistring->setMiddleName($middleNamePR);
				$partyRegistring->setLastName($lastNamePR);
				$partyRegistring->setNin($ninPR);
				$partyRegistring->setTelephone1($telephone1PR);
				$partyRegistring->setTelephone2($telephone2PR);
				$partyRegistring->setDob($dobPR);
				$partyRegistring->setAddress($addressPR);
				$partyRegistring->setEmail($emailPR);
				
				$em->persist($partyRegistring);
				$em->flush();

				$editrecipient->setFirstName($firstNameRP);
				$editrecipient->setCompanyName($companyRP);	
				$editrecipient->setMiddleName($middleNameRP);
				$editrecipient->setLastName($lastNameRP);
				$editrecipient->setNin($ninRP);
				$editrecipient->setTelephone1($telephone1RP);
				$editrecipient->setTelephone2($telephone2RP);
				$editrecipient->setDob($dobRP);
				$editrecipient->setAddress($addressRP);
				$editrecipient->setEmail($emailRP);
				
				
				$em->persist($editrecipient);
				$em->flush();

				$registrationStatus->setStatus(0);
				$em->persist($registrationStatus);
				$em->flush();
				

				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('EDIT_DRB');
				$params['description'] = $this->getLogEventDescriptionAction('EDIT_DRB');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);



				return $this->redirect($this->generateUrl('drp_showpdrb',array('code'=>$code,'number'=>$number)));
			}

				return $this->render('DRPAdminBundle:Pages:editDRB.html.twig',array('editDRB'=>$editDRB,'registrationType'=>$registrationType,'instrumentType'=>$InstrumentType,'lessorType'=>$lessorType,'leaseType'=>$leaseType,'partyRegistring'=>$partyRegistring,'editrecipient'=>$editrecipient,'getType'=>$getType));


		}
		/*===End function Edit  DRB======*/

		/*===Start function for Delete Lease======*/
		public function deleteLeaseAction(Request $request,$id)

		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$number = $session->get('number');
			$em = $this->getDoctrine()->getEntityManager();
			// find the id of user
			$user = $em->getRepository('DRPAdminBundle:Book')->find($id);
			//echo"<pre>";print_r($user->registration_type);die;
			// delete the data of user
			$em->remove($user);
			$em->flush();
			$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('DELETE_LEASE');
				$params['description'] = $this->getLogEventDescriptionAction('DELETE_LEASE');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);
			return $this->redirect($this->generateUrl('drp_showLease',array('code'=>$user->registration_type,'number'=>$number)));


		}	
			
		/*===End function for Delete Lease======*/

		/*===Start function for Delete DRB======*/
		public function deleteDRBAction(Request $request,$id)

		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			$number = $session->get('number');
			// find the id of user
			$user = $em->getRepository('DRPAdminBundle:Book')->find($id);
			//echo"<pre>";print_r($user->registration_type);die;
			// delete the data of user
			$em->remove($user);
			$em->flush();
			$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('DELETE_DRB');
				$params['description'] = $this->getLogEventDescriptionAction('DELETE_DRB');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);
			
			return $this->redirect($this->generateUrl('drp_showpdrb',array('code'=>$user->registration_type,'number'=>$number)));


		}	
			
		/*===End function for Delete DRB======*/


		
		/*===Start function for Manage Lease======*/
		public function ManageLeaseAction(Request $request)

		{
			
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$properties = $em->createQueryBuilder()
			->select('properties.lomp,properties.lessor,properties.lessee,properties.grantor,properties. 	grantee,properties.stamp_duty,properties.registrar_general_initial,properties.registering_party,properties.or_number,properties.reference_number,properties.recipient,or_number,properties.land_situation,properties.id,rtype.type')
			->from('DRPAdminBundle:Book',  'properties')
			->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
			
//echo"<pre>";print_r($properties);die;
			return $this->render('DRPAdminBundle:Pages:manageLease.html.twig',array('properties'=>$properties));
			


		}	
			
		/*===End function for Manage Lease======*/

		/*===Start function for add Lease======*/
		public function addLeaseAction(Request $request)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$code= $_REQUEST['code'];
			
			$session = $this->getRequest()->getSession(); 
			$number = $session->get('number');	
			$em = $this->getDoctrine()->getEntityManager();
			$lastTransaction = $em->createQueryBuilder()
			->select('book')
			->from('DRPAdminBundle:Book',  'book')
			->addOrderBy('book.id', 'DESC')
			  ->setMaxResults(1)
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getArrayResult();
			$getType = $em->getRepository('DRPAdminBundle:RegistrationType')->findOneBy(array('code'=>$code));
			//echo"<pre>";print_r($getType);die;
			
			$registrationType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:RegistrationType',  'reg')


			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
		
			//$type = $_SERVER['HTTP_REFERER'];
			//$newType=explode('/',$type);
	

			//echo "<pre>";print_r($newType);die;

			if($request->getMethod() == 'POST') 
			{
				//echo "<pre>";print_r($_POST);die;

				$registrationType = $request->get('rtype');
				$note = $request->get('notes');
				
				$dateOfReciept=$request->get('drp');
				$situationOfLand = $request->get('sof');
				$dateOfExecution = $request->get('doe');
				//$grantor = $request->get('grantor');
				//$grantee = $request->get('grantee');
				$rir = $request->get('rir');
				$noi = $request->get('noi');
				$stampDuty = $request->get('sd');
				$orNumber = $request->get('or');
				$recipient = $request->get('recipient');
				$partyRegistring = $request->get('pr');
				$registrarGenralIntials = $request->get('rgi');
				$reciepientDate = $request->get('recipientDate');
				//echo $reciepientDate;die;

				
				$lomp = $request->get('lomp');
				
				$company = $request->get('company');
				$firstName = $request->get('firstname');
				$middleName = $request->get('middlename');
				$lastName = $request->get('lastname');
				$nin = $request->get('nin');
				$dob = $request->get('dob');
				$address = $request->get('address');
				$email = $request->get('email');
				$telephone1 = $request->get('tel1');
				$telephone2 = $request->get('tel2');	

				$checkForm = $request->get('typeform');
				$checkForm2 = $request->get('typeform2');

				$companyLs = $request->get('company1');
				$firstNameLs = $request->get('firstname1');
				$middleNameLs = $request->get('middlename1');
				$lastNameLs = $request->get('lastname1');
				$ninLs = $request->get('nin1');
				$dobLs = $request->get('dob1');
				$addressLs = $request->get('address1');
				$emailLs = $request->get('email1');
				$telephone1Ls = $request->get('tel11');
				$telephone2Ls = $request->get('tel21');


				

				$companyLs2 = $request->get('company2');
				$firstNameLs2 = $request->get('firstname2');
				$middleNameLs2 = $request->get('middlename2');
				$lastNameLs2 = $request->get('lastname2');
				$ninLs2 = $request->get('nin2');
				$dobLs2 = $request->get('dob2');
				$addressLs2 = $request->get('address2');
				$emailLs2 = $request->get('email2');
				$telephone1Ls2 = $request->get('tel112');
				$telephone2Ls2 = $request->get('tel212');

				
				$companyLe = $request->get('Lcompany');
				$firstNameLe = $request->get('Lfirstname');
				$middleNameLe = $request->get('Lmiddlename');
				$lastNameLe = $request->get('Llastname');
				$ninLe = $request->get('Lnin');
				$dobLe = $request->get('Ldob');
				$addressLe = $request->get('Laddress');
				$emailLe = $request->get('Lemail');
				$telephone1Le = $request->get('Ltel1');
				$telephone2Le = $request->get('Ltel2');	

				$companyLe1 = $request->get('Lcompany1');
				$firstNameLe1 = $request->get('Lfirstname1');
				$middleNameLe1 = $request->get('Lmiddlename1');
				$lastNameLe1 = $request->get('Llastname1');
				$ninLe1 = $request->get('Lnin1');
				$dobLe1 = $request->get('Ldob1');
				$addressLe1 = $request->get('Laddress1');
				$emailLe1 = $request->get('Lemail1');
				$telephone1Le1 = $request->get('Ltel11');
				$telephone2Le1 = $request->get('Ltel2');	


				$companyLe2 = $request->get('Lcompany2');
				$firstNameLe2 = $request->get('Lfirstname2');
				$middleNameLe2 = $request->get('Lmiddlename2');
				$lastNameLe2 = $request->get('Llastname2');
				$ninLe2 = $request->get('Lnin2');
				$dobLe2 = $request->get('Ldob2');
				$addressLe2 = $request->get('Laddress2');
				$emailLe2 = $request->get('Lemail2');
				$telephone1Le2 = $request->get('Ltel12');
				$telephone2Le2 = $request->get('Ltel22');


				
				$companyPR = $request->get('companynamePR');
				$firstNamePR = $request->get('firstnamePR');
				$middleNamePR = $request->get('middlenamePR');
				$lastNamePR = $request->get('lastnamePR');
				$ninPR = $request->get('ninPR');
				$dobPR = $request->get('dobPR');
				$addressPR = $request->get('addressPR');
				$emailPR = $request->get('emailPR');
				$telephone1PR = $request->get('tel1PR');
				$telephone2PR = $request->get('tel2PR');
			
				
				$firstNameRP = $request->get('firstnameRP');
				$middleNameRP = $request->get('middlenameRP');
				$lastNameRP = $request->get('lastnameRP');
				$ninRP = $request->get('ninRP');
				$dobRP = $request->get('dobRP');
				$addressRP = $request->get('addressRP');
				$emailRP = $request->get('emailRP');
				$telephone1RP = $request->get('tel1RP');
				$telephone2RP = $request->get('tel2RP');	
	

				//$lessor = $request->get('lessor');
				//$lease = $request->get('lease');
	
				//$rd = $request->get('rd');
				
				
				$addLease = new Book();
				$addLease->setRegistrationType($code);
				$addLease->setReceiptDate($dateOfReciept);
				$addLease->setExecutionDate($dateOfExecution);
				$addLease->setLomp($lomp);
				//$addLease->setLessor($lessor);
				//$addLease->setLessee($lease);
				//$addProperty->setGrantor($grantor);
				//$addProperty->setGrantee($grantee);
				$addLease->setStampDuty($stampDuty);
				$addLease->setOrNumber($orNumber);
				$addLease->setNote($note);
				$addLease->setRecipient($recipient);
				//$addProperty->setInstrumentType($noi);
				$addLease->setRecipientDate($reciepientDate);
				$addLease->setRegistrarGeneralInitial($registrarGenralIntials);
				$addLease->setLandSituation($situationOfLand);
				
			

				

				$newSerialNumber = $request->get('hidRegNumber');

				//echo $newReferenceNumber;die;

				$addLease->setSerialNumber($newSerialNumber);
				//$addProperty->setReferenceInRegister($rir);
				$addLease->setRegisteringParty($partyRegistring);
				$em->persist($addLease);
				$em->flush();
				
				$bookId = $addLease->getId();
				

				$registrationStatus = new RegistrationStatus();
				$registrationStatus->setBookId($bookId);
				$registrationStatus->setStatus(0);
				$registrationStatus->setPropertyType('lease');
				$em->persist($registrationStatus);
				$em->flush();


 				$addLessor = new Company();
				$addLessor->setCompanyName($company);
				$addLessor->setFirstName($firstName);
				$addLessor->setMiddleName($middleName);
				$addLessor->setLastName($lastName);
				$addLessor->setNin($nin);
				$addLessor->setTelephone1($telephone1);
				$addLessor->setTelephone2($telephone2);
				$addLessor->setDob($dob);
				$addLessor->setAddress($address);
				$addLessor->setBookId($bookId);
				$addLessor->setType('LR');
				$addLessor->setEmail($email);
				$em->persist($addLessor);
				$em->flush();

				if($firstNameLs||$firstNameLs2!="")
				{
					
					$addLessor1 = new Company();
					$addLessor1->setCompanyName($companyLs);
					$addLessor1->setFirstName($firstNameLs);
					$addLessor1->setMiddleName($middleNameLs);
					$addLessor1->setLastName($lastNameLs);
					$addLessor1->setNin($ninLs);
					$addLessor1->setTelephone1($telephone1Ls);
					$addLessor1->setTelephone2($telephone2Ls);
					$addLessor1->setDob($dobLs);
					$addLessor1->setAddress($addressLs);
					$addLessor1->setType('LR');
					$addLessor1->setEmail($emailLs);
					$addLessor1->setBookId($bookId);
					$em->persist($addLessor1);
					$em->flush();


					$addLessor2 = new Company();
					$addLessor2->setCompanyName($companyLs2);
					$addLessor2->setFirstName($firstNameLs2);
					$addLessor2->setMiddleName($middleNameLs2);
					$addLessor2->setLastName($lastNameLs2);
					$addLessor2->setNin($ninLs2);
					$addLessor2->setTelephone1($telephone1Ls2);
					$addLessor2->setTelephone2($telephone2Ls2);
					$addLessor2->setDob($dobLs2);
					$addLessor2->setAddress($addressLs2);
					$addLessor2->setType('LR');
					$addLessor2->setEmail($emailLs2);
					$addLessor2->setBookId($bookId);
					$em->persist($addLessor2);
					$em->flush();
				}


				
				$addNewLease = new Company();
				$addNewLease->setCompanyName($companyLe);
				$addNewLease->setFirstName($firstNameLe);
				$addNewLease->setMiddleName($middleNameLe);
				$addNewLease->setLastName($lastNameLe);
				$addNewLease->setNin($ninLe);
				$addNewLease->setTelephone1($telephone1Le);
				$addNewLease->setTelephone2($telephone2Le);
				$addNewLease->setDob($dobLe);
				$addNewLease->setAddress($addressLe);
				$addNewLease->setEmail($emailLe);
				$addNewLease->setBookId($bookId);
				$addNewLease->setType('LE');
				$em->persist($addNewLease);
				$em->flush();
				if($firstNameLe1||$firstNameLe2!="")
				{
					$addNewLease1 = new Company();
					$addNewLease1->setCompanyName($companyLe1);
					$addNewLease1->setFirstName($firstNameLe1);
					$addNewLease1->setMiddleName($middleNameLe1);
					$addNewLease1->setLastName($lastNameLe1);
					$addNewLease1->setNin($ninLe1);
					$addNewLease1->setTelephone1($telephone1Le1);
					$addNewLease1->setTelephone2($telephone2Le1);
					$addNewLease1->setDob($dobLe1);
					$addNewLease1->setAddress($addressLe1);
					$addNewLease1->setEmail($emailLe1);
					$addNewLease1->setBookId($bookId);
					$addNewLease1->setType('LE');
					$em->persist($addNewLease1);
					$em->flush();



					$addNewLease2 = new Company();
					$addNewLease2->setCompanyName($companyLe2);
					$addNewLease2->setFirstName($firstNameLe2);
					$addNewLease2->setMiddleName($middleNameLe2);
					$addNewLease2->setLastName($lastNameLe2);
					$addNewLease2->setNin($ninLe2);
					$addNewLease2->setTelephone1($telephone1Le2);
					$addNewLease2->setTelephone2($telephone2Le2);
					$addNewLease2->setDob($dobLe2);
					$addNewLease2->setAddress($addressLe2);
					$addNewLease2->setEmail($emailLe2);
					$addNewLease2->setBookId($bookId);
					$addNewLease2->setType('LE');
					$em->persist($addNewLease2);
					$em->flush();
				}


				$addPartyRegistring = new Company();
				$addPartyRegistring->setCompanyName($companyPR);
				$addPartyRegistring->setFirstName($firstNamePR);
				$addPartyRegistring->setMiddleName($middleNamePR);
				$addPartyRegistring->setLastName($lastNamePR);
				$addPartyRegistring->setNin($ninPR);
				$addPartyRegistring->setTelephone1($telephone1PR);
				$addPartyRegistring->setTelephone2($telephone2PR);
				$addPartyRegistring->setDob($dobPR);
				$addPartyRegistring->setAddress($addressPR);
				$addPartyRegistring->setEmail($emailPR);
				$addPartyRegistring->setType('PR');
				$addPartyRegistring->setBookId($bookId);
				$em->persist($addPartyRegistring);
				$em->flush();

				$addRecipient = new Company();
				$addRecipient->setFirstName($firstNameRP);
				$addRecipient->setMiddleName($middleNameRP);
				$addRecipient->setLastName($lastNameRP);
				$addRecipient->setNin($ninRP);
				$addRecipient->setTelephone1($telephone1RP);
				$addRecipient->setTelephone2($telephone2RP);
				$addRecipient->setDob($dobRP);
				$addRecipient->setAddress($addressRP);
				$addRecipient->setEmail($emailRP);
				$addRecipient->setType('RP');
				$addRecipient->setBookId($bookId);
				$em->persist($addRecipient);
				$em->flush();

				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('ADD_LEASE');
				$params['description'] = $this->getLogEventDescriptionAction('ADD_LEASE');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);
			
				return $this->redirect($this->generateUrl('drp_showLease',array('code'=>$code,'number'=>$number)));


			

		}


			return $this->render('DRPAdminBundle:Pages:addLease.html.twig',array('registrationType'=>$registrationType,'getType'=>$getType));

		}	
		/*===End function for Manage Properties======*/


		/*===Start function for Edit Lease======*/
		public function editLeaseAction(Request $request,$id)

		{	$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$number = $session->get('number');
			$code = $_REQUEST['code'];
			$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:RegistrationType',  'reg')
			//->where('business.type=:type')
			//->setParameter('type', 1)
			->getQuery()
			->getResult();
			
			/*$lessorType = $em->createQueryBuilder()
			->select('lessor')
			->from('DRPAdminBundle:Company',  'lessor')
			->where('lessor.type=:type')
			->andwhere('lessor.book_id=:id')
			->setParameter('id', $id)
			->setParameter('type', 'lessor')
			->getQuery()
			->getResult();*/

			$getType = $em->getRepository('DRPAdminBundle:RegistrationType')->findOneBy(array('code'=>$code));
			$editLease = $em->getRepository('DRPAdminBundle:Book')->find($id);
			$lessorType = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'LR'));
			$leaseType = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'LE'));
			$partyRegistring = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'PR'));
			$editrecipient = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'RP'));
			$registrationStatus = $em->getRepository('DRPAdminBundle:RegistrationStatus')->findOneBy(array('book_id'=>$id));

			
			
			if($request->getMethod() == 'POST') 
			{
				$note = $request->get('notes');
				$dateOfReciept=$request->get('drp');
				$situationOfLand = $request->get('sof');
				$dateOfExecution = $request->get('doe');
				//$grantor = $request->get('grantor');
				//$grantee = $request->get('grantee');
				$rir = $request->get('rir');
				$noi = $request->get('noi');
				$stampDuty = $request->get('sd');
				$orNumber = $request->get('or');
				$recipient = $request->get('recipient');
				//$partyRegistring = $request->get('pr');
				$registrarGenralIntials = $request->get('rgi');
				$reciepientDate = $request->get('recipientDate');
				
				$serialNumber = $request->get('sr');
				$lomp = $request->get('lomp');
				$lessor = $request->get('lessor');
				$lessee = $request->get('lessee');
	
				$rd = $request->get('rd');

				$company = $request->get('company');
				$firstName = $request->get('firstname');
				$middleName = $request->get('middlename');
				$lastName = $request->get('lastname');
				$nin = $request->get('nin');
				$dob = $request->get('dob');
				$address = $request->get('address');
				$email = $request->get('email');
				$telephone1 = $request->get('tel1');
				$telephone2 = $request->get('tel2');	
				
				
				
				$companyLe = $request->get('Lcompany');
				$firstNameLe = $request->get('Lfirstname');
				$middleNameLe = $request->get('Lmiddlename');
				$lastNameLe = $request->get('Llastname');
				$ninLe = $request->get('Lnin');
				$dobLe = $request->get('Ldob');
				$addressLe = $request->get('Laddress');
				$emailLe = $request->get('Lemail');
				$telephone1Le = $request->get('Ltel1');
				$telephone2Le = $request->get('Ltel2');	

				
				$companyPR = $request->get('companynamePR');
				$firstNamePR = $request->get('firstnamePR');
				$middleNamePR = $request->get('middlenamePR');
				$lastNamePR = $request->get('lastnamePR');
				$ninPR = $request->get('ninPR');
				$dobPR = $request->get('dobPR');
				$addressPR = $request->get('addressPR');
				$emailPR = $request->get('emailPR');
				$telephone1PR = $request->get('tel1PR');
				$telephone2PR = $request->get('tel2PR');

				
				$firstNameRP = $request->get('firstnameRP');
				$middleNameRP = $request->get('middlenameRP');
				$lastNameRP = $request->get('lastn ameRP');
				$ninRP = $request->get('ninRP');
				$dobRP = $request->get('dobRP');
				$addressRP = $request->get('addressRP');
				$emailRP = $request->get('emailRP');
				$telephone1RP = $request->get('tel1RP');
				$telephone2RP = $request->get('tel2RP');

				
				$editLease->setNote($note);
				$editLease->setRegistrationType($code);
				$editLease->setReceiptDate($dateOfReciept);
				$editLease->setExecutionDate($dateOfExecution);
				$editLease->setLomp($lomp);
				$editLease->setLessor($lessor);
				$editLease->setLessee($lessee);
				//$addProperty->setGrantor($grantor);
				//$addProperty->setGrantee($grantee);
				$editLease->setStampDuty($stampDuty);
				$editLease->setOrNumber($orNumber);
				$editLease->setRecipient($recipient);
				//$addProperty->setInstrumentType($noi);
				$editLease->setRecipientDate($reciepientDate);
				$editLease->setRegistrarGeneralInitial($registrarGenralIntials);
				$editLease->setLandSituation($situationOfLand);
			
				
				//$addProperty->setReferenceInRegister($rir);
				//$editLease->setRegisteringParty($partyRegistring);
				$em->persist($editLease);
				$em->flush();

				
				$lessorType->setCompanyName($company);
				$lessorType->setFirstName($firstName);
				$lessorType->setMiddleName($middleName);
				$lessorType->setLastName($lastName);
				$lessorType->setNin($nin);
				$lessorType->setTelephone1($telephone1);
				$lessorType->setTelephone2($telephone2);
				$lessorType->setDob($dob);
				$lessorType->setAddress($address);
			
				
				$lessorType->setEmail($email);
				$em->persist($lessorType);
				$em->flush();

				
				$leaseType->setCompanyName($companyLe);
				$leaseType->setFirstName($firstNameLe);
				$leaseType->setMiddleName($middleNameLe);
				$leaseType->setLastName($lastNameLe);
				$leaseType->setNin($ninLe);
				$leaseType->setTelephone1($telephone1Le);
				$leaseType->setTelephone2($telephone2Le);
				$leaseType->setDob($dobLe);
				$leaseType->setAddress($addressLe);
				$leaseType->setEmail($emailLe);
				
				
				$em->persist($leaseType);
				$em->flush();

				
				$partyRegistring->setCompanyName($companyPR);
				$partyRegistring->setFirstName($firstNamePR);
				$partyRegistring->setMiddleName($middleNamePR);
				$partyRegistring->setLastName($lastNamePR);
				$partyRegistring->setNin($ninPR);
				$partyRegistring->setTelephone1($telephone1PR);
				$partyRegistring->setTelephone2($telephone2PR);
				$partyRegistring->setDob($dobPR);
				$partyRegistring->setAddress($addressPR);
				$partyRegistring->setEmail($emailPR);
				
				
				$em->persist($partyRegistring);
				$em->flush();

				$editrecipient->setFirstName($firstNameRP);
				$editrecipient->setMiddleName($middleNameRP);
				$editrecipient->setLastName($lastNameRP);
				$editrecipient->setNin($ninRP);
				$editrecipient->setTelephone1($telephone1RP);
				$editrecipient->setTelephone2($telephone2RP);
				$editrecipient->setDob($dobRP);
				$editrecipient->setAddress($addressRP);
				$editrecipient->setEmail($emailRP);
				
				
				$em->persist($editrecipient);
				$em->flush();

				$registrationStatus->setStatus(0);
				$em->persist($registrationStatus);
				$em->flush();

				

				$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('EDIT_LEASE');
				$params['description'] = $this->getLogEventDescriptionAction('EDIT_LEASE');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);


				return $this->redirect($this->generateUrl('drp_showLease',array('code'=>$code,'number'=>$number)));
			}

				return $this->render('DRPAdminBundle:Pages:editLease.html.twig',array('editLease'=>$editLease,'registrationType'=>$registrationType,'lessorType'=>$lessorType,'leaseType'=>$leaseType,'partyRegistring'=>$partyRegistring,'editrecipient'=>$editrecipient,'getType'=>$getType));


		}
		/*===End function Edit  Lease======*/

		/*===Start function for Delete Payment======*/
		public function deletePaymentAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			// find the id of user
			$user = $em->getRepository('DRPAdminBundle:UserPlan')->find($id);
			// delete the data of user
			$em->remove($user);
			$em->flush();
			return $this->redirect($this->generateUrl('drp_managePayments'));

		}
		/*===End function for Delete Payment======*/

		public function viewDRBAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			// find the id of user
			$viewDRB = $em->getRepository('DRPAdminBundle:Book')->find($id);
			$viewLessor =$em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'GR'));
			$viewLeasse = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'GE'));
			$viewPr = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'PR'));
			$viewRP = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'RP'));

			$getType = $em->createQueryBuilder()
			->select('rtype.type,gI.type as InstrumentType')
			->from('DRPAdminBundle:Book',  'properties')
			->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			->leftJoin('DRPAdminBundle:GlobalInstrument', 'gI', "WITH", "gI.id=properties.instrument_type")
			->where('properties.id=:id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getArrayResult();
		


			$generalInfo = $em->createQueryBuilder()
			->select('user.first_name,user.last_name,rtype.status')
			->from('DRPAdminBundle:Book',  'general')
			->leftJoin('DRPAdminBundle:RegistrationStatus', 'rtype', "WITH", "general.id=rtype.book_id")
			->leftJoin('DRPAdminBundle:User', 'user', "WITH", "user.id=rtype.registrar_general_id")
			->where('general.id=:id')
			->setParameter('id', $id)
			->getQuery()
			->getArrayResult();
			//echo"<pre>";print_r($generalInfo);die;
			

			return $this->render('DRPAdminBundle:Pages:viewDRB.html.twig',array('viewDRB'=>$viewDRB,'viewLessor'=>$viewLessor,'viewLeasse'=>$viewLeasse,'viewPr'=>$viewPr,'viewRP'=>$viewRP,'getType'=>$getType[0]['type'],'generalInfo'=>$generalInfo,'instrumentType'=>$getType[0]['InstrumentType']));



		}

		public function viewLeaseAction(Request $request,$id)
		{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();
			// find the id of user
			$viewLease = $em->getRepository('DRPAdminBundle:Book')->find($id);

			$viewLessor =$em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'LR'));
			//echo"<pre>";print_r($viewLease);die;
			$getType = $em->createQueryBuilder()
			->select('rtype')
			->from('DRPAdminBundle:Book',  'properties')
			->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			->where('properties.id=:id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getArrayResult();
			
			$generalInfo = $em->createQueryBuilder()
			->select('user.first_name,user.last_name,rtype.status')
			->from('DRPAdminBundle:Book',  'general')
			->leftJoin('DRPAdminBundle:RegistrationStatus', 'rtype', "WITH", "general.id=rtype.book_id")
			->leftJoin('DRPAdminBundle:User', 'user', "WITH", "user.id=rtype.registrar_general_id")
			->where('general.id=:id')
			->setParameter('id', $id)
			->getQuery()
			->getArrayResult();
			//echo"<pre>";print_r($getType[0]['type']);die;

			$viewLeasse = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'LE'));
			$viewPr = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'PR'));
			$viewRP = $em->getRepository('DRPAdminBundle:Company')->findOneBy(array('book_id'=>$id,'type'=>'RP'));	
			//echo"<pre>";print_r($viewRP);die;
			
			return $this->render('DRPAdminBundle:Pages:viewLease.html.twig',array('viewLease'=>$viewLease,'viewLessor'=>$viewLessor,'viewLeasse'=>$viewLeasse,'viewPr'=>$viewPr,'viewRP'=>$viewRP,'getType'=>$getType[0]['type'],'generalInfo'=>$generalInfo));


		}
		public function showRegistrationTypeAction(Request $request)
		{

			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$propertyType = $_POST['id'];
			
			$number = $session->get('number');	
			$url = $this->container->getParameter('gbl_upload_path_url');
			$em = $this->getDoctrine()->getEntityManager();
			$regType = $em->createQueryBuilder()
			->select('drb')
			->from('DRPAdminBundle:RegistrationType',  'drb')
			//->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			->where('drb.property_type=:type')
			->setParameter('type', $propertyType)
			->getQuery()
			->getArrayResult();
			//echo"<pre>";print_r($regType);die;
			
			$html = '';
			foreach($regType as $registration)
			{
				//$html.=$registration['type'];
				$html.= '<li id="'.$registration['code'].'">';

				$html.='<a href="'.$url.$registration['property_type'].'/'.$registration['code'].'/'.$number.'">'.$registration['type'].'</a>';
							
				$html.=	'</li>';
			}
		
		return new response($html);
			
		

			//return $this->render('DRPAdminBundle::layout_admin_dashboard.html.twig');


		}	
		
		public function propertiesAction(Request $request,$code)
		{
			
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}			
			$arrProperty = array();
			$arrAllProperties = array();

			$em = $this->getDoctrine()->getEntityManager();
			$properties = $em->createQueryBuilder()
			->select('properties.lomp,properties.id,properties.serial_number,properties.lessor,properties.lessee,properties.grantor,properties. 	grantee,properties.stamp_duty,properties.registrar_general_initial,properties.registering_party,properties.or_number,properties.execution_date,properties.receipt_date,properties.reference_number,properties.recipient,properties.land_situation,properties.id,rtype.type')
			->from('DRPAdminBundle:Book',  'properties')
			->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			->where('properties.registration_type=:type')
			->setParameter('type',$code)
			->addOrderBy('properties.id','DESC')
			->getQuery()
			->getArrayResult();
			//echo"<pre>";print_r($properties);die;

			foreach($properties as $property)
			{

				$arrProperty = $property;

				$arrProperty['lessorFN'] = '';
				$arrProperty['lessorLN'] = '';
 				$arrProperty['lessorTelephone1'] = '';
  				$arrProperty['lessorTelephone2'] = '';
				$arrProperty['lessorNin'] = '';

				$arrProperty['lesseeFN'] = '';
				$arrProperty['lesseeLN'] = '';
				$arrProperty['lesseeNin'] = '';

				$arrProperty['partyRegistringFN'] = '';
				$arrProperty['partyRegistringLN'] = '';
				$arrProperty['partyRegistringNin'] = '';


				$arrProperty['recipientFN'] = '';
				$arrProperty['recipientLN'] = '';
				$arrProperty['recipientNin'] = '';

				$propertyDetail = $em->createQueryBuilder()
				->select('propertyDetail')
				->from('DRPAdminBundle:Company',  'propertyDetail')
				->where('propertyDetail.book_id=:bookId')
				->setParameter('bookId',$property['id'])
				->getQuery()
				->getArrayResult();
				//echo"<pre>";print_r($propertyDetail);die;

				foreach($propertyDetail as $propertyObject)
				{
					if($propertyObject['type']=='LR' )
					{
						$lessorFN = $propertyObject['first_name'];
						$lessorLN = $propertyObject['last_name'];
						$lessorTel1 = $propertyObject['telephone1'];
						$lessorTel2 = $propertyObject['telephone2'];
						$lessorNin = $propertyObject['nin'];
					}


					if($propertyObject['type']=='LE' )
					{
						$lesseeFN = $propertyObject['first_name'];
						$lesseeLN = $propertyObject['last_name'];
						$lesseeTel1 = $propertyObject['telephone1'];
						$lesseeNin = $propertyObject['nin'];
					}

					if($propertyObject['type']=='PR' )
					{
						$partyRegistringFN = $propertyObject['first_name'];
						$partyRegistringLN = $propertyObject['last_name'];
						$partyRegistringNin = $propertyObject['nin'];
					}
					if($propertyObject['type']=='RP' )
					{
						$recipientFN = $propertyObject['first_name'];
						$recipientLN = $propertyObject['last_name'];
						$recipientNin = $propertyObject['nin'];	
					}
					
				}
				$arrProperty['lessorFN'] = $lessorFN;
				$arrProperty['lessorLN'] = $lessorLN;
				$arrProperty['lessorTelephone1'] = $lessorTel1;
				$arrProperty['lessorNin'] = $lessorNin;
  				

				$arrProperty['lesseeFN'] = $lesseeFN;
				$arrProperty['lesseeLN'] = $lesseeLN;
				$arrProperty['lesseeTelephone1'] = $lessorTel1;
				$arrProperty['lesseeNin'] = $lesseeNin;

				$arrProperty['partyRegistringFN'] = $partyRegistringFN;
				$arrProperty['partyRegistringLN'] = $partyRegistringLN;
				$arrProperty['partyRegistringNin'] = $partyRegistringNin;				

				$arrProperty['recipientFN'] = $recipientFN;
				$arrProperty['recipientLN'] = $recipientLN;
				$arrProperty['recipientNin'] = $recipientNin;


				//echo"<pre>";print_r($arrProperty);die;

				array_push($arrAllProperties, $arrProperty);

			}
			//echo"<pre>";print_r($arrAllProperties);die;


			return $this->render('DRPAdminBundle:Pages:lease.html.twig',array('properties'=>$arrAllProperties,'b'=>$code));


		}
			public function showpdrbAction(Request $request,$code)
			{
				
				
				$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}		
				$em = $this->getDoctrine()->getEntityManager();
				$properties = $em->createQueryBuilder()
				->select('properties.lomp,properties.receipt_date,gI.type,properties.id,properties.instrument_type,properties.execution_date,properties.reference_number,properties.grantor,properties. 	grantee,properties.stamp_duty,properties.registrar_general_initial,properties.registering_party,properties.serial_number,properties.or_number,properties.land_situation,properties.id')
				->from('DRPAdminBundle:Book',  'properties')
				->leftJoin('DRPAdminBundle:GlobalInstrument', 'gI', "WITH", "properties.instrument_type=gI.id")
				->where('properties.registration_type=:type')
				->setParameter('type',$code)
				->orderBy('properties.id','DESC')
				->getQuery()
				->getArrayResult();
			//echo"<pre>";print_r($properties);die;
				$arrAllProperties = array();
				foreach($properties as $property)
				{

				$arrProperty = $property;

				$arrProperty['grantorFN'] = '';
				$arrProperty['grantorLN'] = '';
				$arrProperty['grantortel1'] = '';
				$arrProperty['grantorNin'] = '';
				

				$arrProperty['granteeFN'] = '';
				$arrProperty['granteeLN'] = '';
				$arrProperty['granteetel1'] = '';
				$arrProperty['granteeNin'] = '';
				
				$arrProperty['partyRegistringFN'] = '';
				$arrProperty['partyRegistringLN'] = '';
				$arrProperty['partyRegistringNin'] = '';


				$arrProperty['recipientFN'] = '';
				$arrProperty['recipientLN'] = '';
				$arrProperty['recipientNin'] = '';

				$propertyDetail = $em->createQueryBuilder()
				->select('propertyDetail')
				->from('DRPAdminBundle:Company',  'propertyDetail')
				->where('propertyDetail.book_id=:bookId')
				->setParameter('bookId',$property['id'])
				->getQuery()
				->getArrayResult();
				//echo"<pre>";print_r($propertyDetail);die;

				foreach($propertyDetail as $propertyObject)
				{
					if($propertyObject['type']=='GR' )
					{
						$grantorFN = $propertyObject['first_name'];
						$grantorLN = $propertyObject['last_name'];
						$grantortel1 = $propertyObject['telephone1'];
						$grantorNin = $propertyObject['nin'];
					}
						//echo $lessorFN;die;
						
					if($propertyObject['type']=='GE' )
					{
						$granteeFN = $propertyObject['first_name'];
						$granteeLN = $propertyObject['last_name'];
						$granteetel1 = $propertyObject['telephone1'];
						$granteeNin = $propertyObject['nin'];
					}

					if($propertyObject['type']=='PR' )
					{
						$partyRegistringFN = $propertyObject['first_name'];
						$partyRegistringLN = $propertyObject['last_name'];
						$partyRegistringNin = $propertyObject['nin'];
					}
					if($propertyObject['type']=='RP' )
					{
						$recipientFN = $propertyObject['first_name'];
						$recipientLN = $propertyObject['last_name'];
						$recipientNin = $propertyObject['nin'];	
					}
					
				}
				$arrProperty['grantorFN'] = $grantorFN;
				$arrProperty['grantorLN'] = $grantorLN;
				$arrProperty['grantortel1'] = $grantortel1;
				$arrProperty['grantorNin'] = $grantorNin;

				$arrProperty['granteeFN'] = $granteeFN;
				$arrProperty['granteeLN'] = $granteeLN;
				$arrProperty['granteetel1'] = $granteetel1;
				$arrProperty['granteeNin'] = $granteeNin;

				$arrProperty['partyRegistringFN'] = $partyRegistringFN;
				$arrProperty['partyRegistringLN'] = $partyRegistringLN;
				$arrProperty['partyRegistringNin'] = $partyRegistringNin;				

				$arrProperty['recipientFN'] = $recipientFN;
				$arrProperty['recipientLN'] = $recipientLN;
				$arrProperty['recipientNin'] = $recipientNin;


				//echo"<pre>";print_r($arrProperty);die;

				array_push($arrAllProperties, $arrProperty);

			}
				


				return $this->render('DRPAdminBundle:Pages:drb.html.twig',array('properties'=>$arrAllProperties,'code'=>$code));


		}
		
		public function genrateReferenceNumberAction()
		{

			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$registrationType = $_POST['code'];
			$regTypeCode = substr($registrationType, 0, 1);
			$currentYear = date("Y");
			
			$em = $this->getDoctrine()->getEntityManager();
				$lastTransaction = $em->createQueryBuilder()
				->select('book')
				->from('DRPAdminBundle:Book',  'book')
				->where('book.registration_type=:type')
				->setParameter('type', $registrationType)
				
				->addOrderBy('book.id', 'DESC')
				 ->setMaxResults(1)
				->getQuery()
				->getArrayResult();
			if( isset($lastTransaction) && is_array($lastTransaction) && count($lastTransaction)>0 )
			{

				$lastRecordReferenceNumber = $lastTransaction[0]['serial_number'];

				$arrRefNum = explode('/', $lastRecordReferenceNumber);

				$lastId = substr($arrRefNum[0], 1);
				$lastYear = $arrRefNum[1];

				if( $lastYear == $currentYear )
				{
				    $newId = $lastId + 1;
				}
				else
				{
				    $newId = 1;
				}
			}
			else
			{
				$newId = 1;
			}

				$newReferenceNumber = $regTypeCode.$newId.'/'.$currentYear;
				 return new response($newReferenceNumber);
				echo $newReferenceNumber;die;
		
		}


		/*=========Reference  number for drb=======*/ 
		public function genrateSerialNumberAction()
		{
			
			$code = $_REQUEST['code'];
			$registrationType = $_POST['code'];
			$currentYear = date("Y");
			
			$em = $this->getDoctrine()->getEntityManager();
				$lastTransaction = $em->createQueryBuilder()
				->select('book')
				->from('DRPAdminBundle:Book',  'book')
				->where('book.registration_type=:type')
				->setParameter('type', $code)
				->addOrderBy('book.id', 'DESC')
				  ->setMaxResults(1)	
				->getQuery()
				->getArrayResult();
//echo "<PRE>";print_r($lastTransaction);die;
				if( isset($lastTransaction) && is_array($lastTransaction) && count($lastTransaction)>0 )
				{	
					$lastRecordReferenceNumber = $lastTransaction[0]['reference_number'];

					//$regTypeCode = substr($registrationType, 0, 1);

					$arrRefNum = explode('/', $lastRecordReferenceNumber);

					$lastId = $arrRefNum[0];
					$lastYear = $arrRefNum[1];

					$arrLastYear = explode(' ', $lastYear);
					$lastYear = $arrLastYear[0];

					//echo $lastId."---".$lastYear."---".$currentYear;die;
					if( $lastYear == $currentYear )
					{
					    $newId = $lastId + 1;
					}
					else
					{
					    $newId = 1;
					}
				}
				else
				{
					$newId = 1;
				}

				$newReferenceNumber = $newId.'/'.$currentYear.' '.$registrationType;
				 return new response($newReferenceNumber);
				echo $newReferenceNumber;die;
		
		}
		/*=========Reference  number for drb=======*/ 


		public function getSerialNumberAction()
		{
			$value = $_POST['value'];
			$em = $this->getDoctrine()->getEntityManager();
			$serialNumber = $em->createQueryBuilder()
			->select('book')
			->from('DRPAdminBundle:Book',  'book')
			->leftJoin('DRPAdminBundle:RegistrationType', 'reg', "WITH", "reg.code=book.registration_type ")
			->where('book.serial_number like :serial')
			->setParameter('serial', $value.'%')
			->andwhere('reg.property_type like :pType')
			->setParameter('pType', 'lease')
			->getQuery()
			->getArrayResult();
			//echo"<pre>";print_r($serialNumber);die;
					
			$html = '';

			$html.= '<ul>';		
			foreach($serialNumber as $totalNumbers)
			{
				$html.='<div class="hover_color"><li id="'.$totalNumbers['serial_number'].'" onclick="serialNumber(this.id);">'.$totalNumbers['serial_number'].'</li></div>';
			
			}
			$html.= '</ul>';
			return new response($html);

		}

		function checkSerialNumberAction()
		{
			$serialNumber = $_POST['serial'];
	
			$em = $this->getDoctrine()->getEntityManager();
			$serialNumber = $em->createQueryBuilder()
			->select('serial')
			->from('DRPAdminBundle:Book',  'serial')
			->where('serial.serial_number=:serial')
			->setParameter('serial', $serialNumber)
			->getQuery()
			->getArrayResult();

		if(count($serialNumber) > 0)
		{
			return new response('SUCCESS');	
		}
		
		return new response('FAILURE');

		
		
		
		
	}

	function addSearchesAction(Request $request )
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$id  = $_REQUEST['code'];
		if($request->getMethod() == 'POST') 
		{
			
			$searches = $request->get('searches');


			$em = $this->getDoctrine()->getEntityManager();

			$userInfo = $em->createQueryBuilder()
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			->where('user.id=:userId')
			->setParameter('userId', $id)
			->getQuery()
			->getArrayResult();

			//echo "<pre>";print_r($userInfo[0]['search_count_total']);die;
			$totalSearches = $userInfo[0]['search_count_total'] + $searches;
			$balanceSearches = $userInfo[0]['search_count_balance'] + $searches;

			$updateSearches = $em->createQueryBuilder()
			->select('search')
			->update('DRPAdminBundle:User',  'search')
			->set('search.search_count_total', ':totalSearches')
			->setParameter('totalSearches', $totalSearches)
			->where('search.id=:userId')
			->setParameter('userId', $id)
			->getQuery()
			->getResult();
	
			$updateSearches = $em->createQueryBuilder()
			->select('search')
			->update('DRPAdminBundle:User',  'search')
			->set('search.search_count_balance', ':totalSearches')
			->setParameter('totalSearches', $balanceSearches)
			->where('search.id=:userId')
			->setParameter('userId', $id)
			->getQuery()
			->getResult();
			

			 return $this->redirect($this->generateUrl('drp_searchQuota'));

		}
		return $this->render('DRPAdminBundle:Pages:addSearches.html.twig');

	
	}

	function removeSearchesAction(Request $request)
	{

		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$id  = $_REQUEST['code'];
		if($request->getMethod() == 'POST') 
		{
			
			$searches = $request->get('searches');
	

			$em = $this->getDoctrine()->getEntityManager();

			$userInfo = $em->createQueryBuilder()
			->select('user')
			->from('DRPAdminBundle:User',  'user')
			->where('user.id=:userId')
			->setParameter('userId', $id)
			->getQuery()
			->getArrayResult();

			//echo "<pre>";print_r($userInfo[0]['search_count_total']);die;
			$totalSearches = $userInfo[0]['search_count_total'] - $searches;

			$balanceSearches = $userInfo[0]['search_count_balance'] - $searches;	

			$updateSearches = $em->createQueryBuilder()
			->select('search')
			->update('DRPAdminBundle:User',  'search')
			->set('search.search_count_total', ':totalSearches')
			->setParameter('totalSearches', $totalSearches)
			->where('search.id=:userId')
			->setParameter('userId', $id)
			->getQuery()
			->getResult();


			$balancedSearches = $em->createQueryBuilder()
			->select('search')
			->update('DRPAdminBundle:User',  'search')
			->set('search.search_count_balance', ':totalSearches')
			->setParameter('totalSearches', $balanceSearches)
			->where('search.id=:userId')
			->setParameter('userId', $id)
			->getQuery()
			->getResult();




			 return $this->redirect($this->generateUrl('drp_searchQuota'));

		}
		return $this->render('DRPAdminBundle:Pages:removeSearches.html.twig');

	
	}


	function manageAdminAction(Request $request)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		$admin = $em->createQueryBuilder()
			->select('admin')
			->from('DRPAdminBundle:User',  'admin')
			->where('admin.type=:type')
			->setParameter('type', 3)
			->getQuery()
			->getResult();
		
		return $this->render('DRPAdminBundle:Pages:manageAdmin.html.twig',array('admin'=>$admin));
	}


	function manageGeneralAction(Request $request)
	{
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
			$em = $this->getDoctrine()->getEntityManager();			
			$general = $em->createQueryBuilder()
			->select('general')
			->from('DRPAdminBundle:User',  'general')
			->where('general.type=:type')
			->setParameter('type', 2)
			->getQuery()
			->getResult();
		return $this->render('DRPAdminBundle:Pages:manageGeneral.html.twig',array('general'=>$general));
	}		
	function addAdminAction(Request $request)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();	
		if($request->getMethod() == 'POST') 
		{
			$sourcePath = $file = $_FILES['images']['name'];
			 $file1  = $_FILES['images']['tmp_name'];  
    			move_uploaded_file($_FILES["images"]["tmp_name"],
      			"uploads/user/" . $_FILES["images"]["name"]);
			$firstName = $request->get('firstname');
			$middleName = $request->get('middlename');
			$password = $request->get('password');
			$lastName = $request->get('lastname');
			$email = $request->get('email');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$nin = $request->get('nin');
			$addUser = new User();
			$addUser->setFirstName($firstName);
			$addUser->setPassword(md5($password));
			$addUser->setMiddleName($middleName);
			$addUser->setLastName($lastName);
			$addUser->setEmail($email);
			$addUser->setTelephone1($telephone1);
			$addUser->setTelephone2($telephone2);
			$addUser->setNin($nin);	
			$addUser->setPicture($sourcePath);	
			$addUser->setStatus(1);
			$addUser->setType(3);

			$em->persist($addUser);
			$em->flush();

			$date=date("Y/m/d.");
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= 'From: <support@rdrp.com>' . "\r\n";
			$to = $email;
			$subject = "Admin Registration";
			$txt='Hello '. $firstName.' '. $lastName.',<br><br>Your account has been created on '.$date.'<br><br>Email is: <b>'.	$email.'</b>'.'and your password is'. $password;
			
			/*if( $type == 4 )
			{
				$txt.='<br><br>Please note your unique token number (To be shown at Bill Desk) : <b>'.$token.'</b>';
			}*/
			
			mail($to,$subject,$txt,$headers);	
		//echo $txt;die;
			
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			$params['event'] = $this->getLogEventTitleAction('ADD_USER');
			$params['description'] = $this->getLogEventDescriptionAction('ADD_USER');
			$params['userId'] =$session->get('userId');
			$params['ipAddress'] = $ipAddress;
			$params['creatorId'] = $session->get('userId');
			$this->setLogAction($params);


			return $this->redirect($this->generateUrl('drp_admin'));

		}	
		return $this->render('DRPAdminBundle:Pages:addAdmin.html.twig');
	}	
	function addGeneralAction(Request $request)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		if($request->getMethod() == 'POST') 
		{

			$em = $this->getDoctrine()->getEntityManager();	
			$sourcePath = $file = $_FILES['images']['name'];
			 $file1  = $_FILES['images']['tmp_name'];  
    			move_uploaded_file($_FILES["images"]["tmp_name"],
      			"uploads/user/" . $_FILES["images"]["name"]);
			$firstName = $request->get('firstname');
			$middleName = $request->get('middlename');
			$lastName = $request->get('lastname');
			$email = $request->get('email');
			$passcode = $request->get('passcode');
			$password = $request->get('password');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$nin = $request->get('nin');
			$addUser = new User();
			$addUser->setFirstName($firstName);
			$addUser->setMiddleName($middleName);
			$addUser->setLastName($lastName);
			$addUser->setEmail($email);
			$addUser->setPassword(md5($password));
			$addUser->setTelephone1($telephone1);
			$addUser->setTelephone2($telephone2);
			$addUser->setNin($nin);
			$addUser->setStatus(1);

			$addUser->setPicture($sourcePath);
			$addUser->setType(2);
			$addUser->setPasscode($passcode);
			
			
			$em->persist($addUser);
			$em->flush();
			$date=date("Y/m/d.");
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= 'From: <support@rdrp.com>' . "\r\n";
			$to = $email;
			$subject = "Registrar General Registration";
			$txt='Hello '. $firstName.' '. $lastName.',<br><br>Your have created account on '.$date.'<br><br>Email is: <b>'.	$email.'</b>'.'and your password is'.$password;
			mail($to,$subject,$txt,$headers);

			$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('ADD_REGISTRAR_GENERAL');
				$params['description'] = $this->getLogEventDescriptionAction('ADD_REGISTRAR_GENERAL');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);

			return $this->redirect($this->generateUrl('drp_general'));
		}
	
		return $this->render('DRPAdminBundle:Pages:addGeneral.html.twig');
	}	


	function editGeneralAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		$editGeneral = $em->getRepository('DRPAdminBundle:User')->find($id);
		
		if($request->getMethod() == 'POST') 
		{
			$sourcePath = $file = $_FILES['images']['name'];
			$hidImage = $request->get('hidImage');
			 $file1  = $_FILES['images']['tmp_name'];  
    			move_uploaded_file($_FILES["images"]["tmp_name"],
      			"uploads/user/" . $_FILES["images"]["name"]);
			$hidImage = $request->get('hidImage');
			$firstName = $request->get('firstname');
			$middleName = $request->get('middlename');
			$lastName = $request->get('lastname');
			$email = $request->get('email');
			$passcode = $request->get('passcode');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$nin = $request->get('nin');
			
			$editGeneral->setFirstName($firstName);
			$editGeneral->setMiddleName($middleName);
			$editGeneral->setLastName($lastName);
			$editGeneral->setEmail($email);
			$editGeneral->setTelephone1($telephone1);
			$editGeneral->setTelephone2($telephone2);
			$editGeneral->setNin($nin);
			$editGeneral->setStatus(1);
			if($sourcePath == "")
			{
				$editGeneral->setPicture($hidImage);
			}

			else
			{
				$editGeneral->setPicture($sourcePath);
			}
			
			
			$editGeneral->setPasscode($passcode);
			
			
			$em->persist($editGeneral);
			$em->flush();

			$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('EDIT_REGISTRAR_GENERAL');
				$params['description'] = $this->getLogEventDescriptionAction('EDIT_REGISTRAR_GENERAL');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);
			return $this->redirect($this->generateUrl('drp_general'));
		}
	
		return $this->render('DRPAdminBundle:Pages:editGeneral.html.twig',array('editGeneral'=>$editGeneral));
	}	
		
	function editAdminAction(Request $request,$id)
	{

		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		$editAdmin = $em->getRepository('DRPAdminBundle:User')->find($id);
		$session = $this->getRequest()->getSession(); 
		if($request->getMethod() == 'POST') 
		{
			$sourcePath = $file = $_FILES['images']['name'];
			 $file1  = $_FILES['images']['tmp_name'];  
    			move_uploaded_file($_FILES["images"]["tmp_name"],
      			"uploads/user/" . $_FILES["images"]["name"]);
			$hidImage = $request->get('hidImage');
			$firstName = $request->get('firstname');
			$middleName = $request->get('middlename');
			$lastName = $request->get('lastname');
			$email = $request->get('email');
			$passcode = $request->get('passcode');
			$telephone1 = $request->get('tel1');
			$telephone2 = $request->get('tel2');
			$nin = $request->get('nin');
			
			$editAdmin->setFirstName($firstName);
			$editAdmin->setMiddleName($middleName);
			$editAdmin->setLastName($lastName);
			$editAdmin->setEmail($email);
			$editAdmin->setTelephone1($telephone1);
			$editAdmin->setTelephone2($telephone2);
			$editAdmin->setNin($nin);
			$editAdmin->setStatus(1);
			if($sourcePath == "")
			{
				$editAdmin->setPicture($hidImage);
			}

			else
			{
				$editAdmin->setPicture($sourcePath);
			}
			
			$editAdmin->setPasscode($passcode);
			
			
			$em->persist($editAdmin);
			$em->flush();

			$ipAddress = $_SERVER['REMOTE_ADDR'];
				$params['event'] = $this->getLogEventTitleAction('EDIT_ADMIN');
				$params['description'] = $this->getLogEventDescriptionAction('EDIT_ADMIN');
				$params['userId'] =$session->get('userId');
				$params['ipAddress'] = $ipAddress;
				$params['creatorId'] = $session->get('userId');
				$this->setLogAction($params);

			return $this->redirect($this->generateUrl('drp_admin'));
		}
	
	
		return $this->render('DRPAdminBundle:Pages:editAdmin.html.twig',array('editAdmin'=>$editAdmin));
	}

	/*===Start function for delete user======*/
	public function deleteAdminAction(Request $request,$id)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of user
		$user = $em->getRepository('DRPAdminBundle:User')->find($id);
		// delete the data of user
		$em->remove($user);
		$em->flush();

		$session = $this->getRequest()->getSession(); 
		
			
		$ipAddress = $_SERVER['REMOTE_ADDR'];
		$params['event'] = $this->getLogEventTitleAction('DELETE_ADMIN');
		$params['description'] = $this->getLogEventDescriptionAction('DELETE_ADMIN');
		$params['userId'] =$session->get('userId');
		$params['ipAddress'] = $ipAddress;
		$params['creatorId'] = $session->get('userId');
		$this->setLogAction($params);
		return $this->redirect($this->generateUrl('drp_admin'));
		

	}
	/*===End function for delete user======*/
	/*===Start function for delete user======*/
	public function deleteGeneralAction(Request $request,$id)
	{
	
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		// find the id of user
		$user = $em->getRepository('DRPAdminBundle:User')->find($id);
		// delete the data of user
		$em->remove($user);
		$em->flush();

		$session = $this->getRequest()->getSession(); 
		$ipAddress = $_SERVER['REMOTE_ADDR'];
		$params['event'] = $this->getLogEventTitleAction('DELETE_REGISTRAR_GENERAL');
		$params['description'] = $this->getLogEventDescriptionAction('DELETE_REGISTRAR_GENERAL');
		$params['userId'] =$session->get('userId');
		$params['ipAddress'] = $ipAddress;
		$params['creatorId'] = $session->get('userId');
		$this->setLogAction($params);
			

		return $this->redirect($this->generateUrl('drp_general'));
		

	}
	/*===End function for delete user======*/

	public function viewGeneralAction(Request $request,$id)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		$viewGeneral = $em->getRepository('DRPAdminBundle:User')->find($id);
		return $this->render('DRPAdminBundle:Pages:viewGeneral.html.twig',array('viewGeneral'=>$viewGeneral));
	}

	public function viewAdminAction(Request $request,$id)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
		$viewAdmin = $em->getRepository('DRPAdminBundle:User')->find($id);
		return $this->render('DRPAdminBundle:Pages:viewAdmin.html.twig',array('viewAdmin'=>$viewAdmin));

	}

	public function leaseRegistrationTypeAction(Request $request)
	{
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}
		$em = $this->getDoctrine()->getEntityManager();
			$registrationType = $em->createQueryBuilder()
			->select('reg')
			->from('DRPAdminBundle:RegistrationType',  'reg')
			->where('reg.property_type=:type')
			->setParameter('type', 'lease')
			->getQuery()
			->getResult();
			return $this->render('DRPAdminBundle:Pages:leaseRegistrationType.html.twig',array('registrationType'=>$registrationType));

	}
	public function viewDetailAction(Request $request,$id)
	{
		
		$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}	
		$em = $this->getDoctrine()->getEntityManager();
			$viewDetail = $em->createQueryBuilder()
			->select('log,user.lomp,user.type,user.reference_number,gI.type as instrumentType,rType.type as RegistrationType,rType.property_type,user.serial_number,user.or_number,user.execution_date,user.receipt_date,user.land_situation')
			->from('DRPAdminBundle:SearchHistory',  'user')
			->leftJoin('DRPAdminBundle:Log', 'log', "WITH", "user.user_id=log.user_id")
			->leftJoin('DRPAdminBundle:RegistrationType', 'rType', "WITH", "rType.code=user.type")
			->leftJoin('DRPAdminBundle:GlobalInstrument', 'gI', "WITH", "gI.id=user.instrument_type")
			->where('log.id=:id')
			->setParameter('id', $id)
			->getQuery()
			->getResult();
		
		
		return $this->render('DRPAdminBundle:Pages:viewDetail.html.twig',array('viewDetail'=>$viewDetail,'type'=>$viewDetail[0]['property_type']));
	}	

/*===========Pdf Code Starts Here============*/

		public function genratePdfLeaseAction(Request $request,$code)
		{

			ini_set('max_execution_time', 10000000); 

			$url = $this->container->getParameter('gbl_pdf_path_url');	
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}			
			$arrProperty = array();
			$arrAllProperties = array();

			$em = $this->getDoctrine()->getEntityManager();
			$properties = $em->createQueryBuilder()
			->select('properties.lomp,properties.id,properties.serial_number,properties.lessor,properties.lessee,properties.grantor,properties. 	grantee,properties.stamp_duty,properties.registrar_general_initial,properties.registering_party,properties.or_number,properties.execution_date,properties.receipt_date,properties.reference_number,properties.recipient,properties.land_situation,properties.id,rtype.type')
			->from('DRPAdminBundle:Book',  'properties')
			->leftJoin('DRPAdminBundle:RegistrationType', 'rtype', "WITH", "rtype.code=properties.registration_type")
			->where('properties.registration_type=:type')
			->setParameter('type',$code)
			->addOrderBy('properties.id','DESC')
			->getQuery()
			->getArrayResult();
			//echo"<pre>";print_r($properties);die;

			foreach($properties as $property)
			{

				$arrProperty = $property;

				$arrProperty['lessorFN'] = '';
				$arrProperty['lessorLN'] = '';
 				$arrProperty['lessorTelephone1'] = '';
  				$arrProperty['lessorTelephone2'] = '';
				$arrProperty['lessorNin'] = '';

				$arrProperty['lesseeFN'] = '';
				$arrProperty['lesseeLN'] = '';
				$arrProperty['lesseeNin'] = '';

				$arrProperty['partyRegistringFN'] = '';
				$arrProperty['partyRegistringLN'] = '';
				$arrProperty['partyRegistringNin'] = '';


				$arrProperty['recipientFN'] = '';
				$arrProperty['recipientLN'] = '';
				$arrProperty['recipientNin'] = '';

				$propertyDetail = $em->createQueryBuilder()
				->select('propertyDetail')
				->from('DRPAdminBundle:Company',  'propertyDetail')
				->where('propertyDetail.book_id=:bookId')
				->setParameter('bookId',$property['id'])
				->getQuery()
				->getArrayResult();
				//echo"<pre>";print_r($propertyDetail);die;

				foreach($propertyDetail as $propertyObject)
				{
					if($propertyObject['type']=='LR' )
					{
						$lessorFN = $propertyObject['first_name'];
						$lessorLN = $propertyObject['last_name'];
						$lessorTel1 = $propertyObject['telephone1'];
						$lessorTel2 = $propertyObject['telephone2'];
						$lessorNin = $propertyObject['nin'];
					}


					if($propertyObject['type']=='LE' )
					{
						$lesseeFN = $propertyObject['first_name'];
						$lesseeLN = $propertyObject['last_name'];
						$lesseeTel1 = $propertyObject['telephone1'];
						$lesseeNin = $propertyObject['nin'];
					}

					if($propertyObject['type']=='PR' )
					{
						$partyRegistringFN = $propertyObject['first_name'];
						$partyRegistringLN = $propertyObject['last_name'];
						$partyRegistringNin = $propertyObject['nin'];
					}
					if($propertyObject['type']=='RP' )
					{
						$recipientFN = $propertyObject['first_name'];
						$recipientLN = $propertyObject['last_name'];
						$recipientNin = $propertyObject['nin'];	
					}
					
				}
				$arrProperty['lessorFN'] = $lessorFN;
				$arrProperty['lessorLN'] = $lessorLN;
				$arrProperty['lessorTelephone1'] = $lessorTel1;
				$arrProperty['lessorNin'] = $lessorNin;
  				

				$arrProperty['lesseeFN'] = $lesseeFN;
				$arrProperty['lesseeLN'] = $lesseeLN;
				$arrProperty['lesseeTelephone1'] = $lessorTel1;
				$arrProperty['lesseeNin'] = $lesseeNin;

				$arrProperty['partyRegistringFN'] = $partyRegistringFN;
				$arrProperty['partyRegistringLN'] = $partyRegistringLN;
				$arrProperty['partyRegistringNin'] = $partyRegistringNin;				

				$arrProperty['recipientFN'] = $recipientFN;
				$arrProperty['recipientLN'] = $recipientLN;
				$arrProperty['recipientNin'] = $recipientNin;


				//echo"<pre>";print_r($arrProperty);die;

				array_push($arrAllProperties, $arrProperty);

			}
			//echo"<pre>";print_r($arrAllProperties);die;
			$typeR = $em->getRepository('DRPAdminBundle:RegistrationType')->findOneBy(array('code'=>$code));
			$html = '';
			$html.= '<img style="width:20%;margin-left:38%;"src='.$url.'><br>';
			$html.='<h3 style="text-align:center;">Records of '.$typeR->type.' </h3>';
			$html.= $this->render('DRPAdminBundle:Pages:lease.html.twig',array('properties'=>$arrAllProperties,'b'=>$code));
			$html.= '<style>.navbar-collapse.collapse {
   				 display: hidden !important;
  
				}
				.page-header.navbar.navbar-fixed-top {
				    display: none;
				    z-index: 9995;
				}.btn-group.pull-right
				{
				display: none !important;
				}
				.modal-dialog
				{
				display: none !important;
				}.action
				{
				display:none;
				}.col-md-6 {
				    display: none !important;
				}
				.portlet-title
				{
				display:none !important;
				}.portlet-body.port {
				    display: none;
				}
				.logo-default {
				    display: none;
				}
				.pdf_Action
				{

					display:none !important;
				}
				table.table-bordered.dataTable {
				    border-collapse: separate !important;
				}
				.table-scrollable > .table-bordered {
				    border: 0 none;
				}
				.table-scrollable > .table {
				    background-color: #fff;
				    margin: 0 !important;
				    width: 100% !important;
				}
				table.dataTable {
				    clear: both;
				    margin-bottom: 6px !important;
				    margin-top: 6px !important;
				    max-width: none !important;
				}
				.dataTable {
				    clear: both;
				    margin-top: 5px;
				    width: 100%;
				}
				.table-bordered {
				    border: 1px solid #ddd;
				}
				.table {
				    margin-bottom: 20px;
				    max-width: 100%;
				    width: 100%;
				}
				table {
				    background-color: transparent;
				}
				table {
				    border-collapse: collapse;
				    border-spacing: 0;
				}.table-striped > tbody > tr:nth-of-type(2n+1) {
				    background-color: #f9f9f9;
				}.table-scrollable > .table-bordered > thead > tr > th:first-child, .table-scrollable > .table-bordered > tbody > tr > th:first-child, .table-scrollable > .table-bordered > tfoot > tr > th:first-child, .table-scrollable > .table-bordered > thead > tr > td:first-child, .table-scrollable > .table-bordered > tbody > tr > td:first-child, .table-scrollable > .table-bordered > tfoot > tr > td:first-child {
				    border-left: 0 none;
				}table.table-bordered tbody th, table.table-bordered tbody td {
				    border-bottom-width: 0;
				    border-left-width: 0;
				}
				.table-bordered > tbody > tr > td, .table-bordered > tbody > tr > th, .table-bordered > tfoot > tr > td, .table-bordered > tfoot > tr > th, .table-bordered > thead > tr > td, .table-bordered > thead > tr > th {
				    border: 1px solid #ddd;
				}
				.table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
				    border-top: 1px solid #ddd;
				    line-height: 1.42857;
				    padding: 8px;
				    vertical-align: top;
				}
				table.dataTable td, table.dataTable th {
				    box-sizing: content-box;
				}
				td, th {
				    padding: 0;
				    font-size:10px;!important;		
				}
			
				</style>';
   			 $dompdf = $this->get('slik_dompdf');

   
   			 $dompdf->getpdf($html);

  
   			 $dompdf->stream($typeR->type.'.pdf');

   
  			  $pdfoutput = $dompdf->output();
		}

		public function genratePdfDRBAction(Request $request,$code)
			{
			ini_set('max_execution_time', 10000000); 	
			$url = $this->container->getParameter('gbl_pdf_path_url');	
			$session = $this->getRequest()->getSession();
			if($session->get('type') != 1)
			{
				//if user is login then it will be redirect to login page    			
			 return $this->redirect($this->generateUrl('drp_adminLogin'));
			}		
				$em = $this->getDoctrine()->getEntityManager();
				$properties = $em->createQueryBuilder()
				->select('properties.lomp,properties.receipt_date,gI.type,properties.id,properties.instrument_type,properties.execution_date,properties.reference_number,properties.grantor,properties. 	grantee,properties.stamp_duty,properties.registrar_general_initial,properties.registering_party,properties.serial_number,properties.or_number,properties.land_situation,properties.id')
				->from('DRPAdminBundle:Book',  'properties')
				->leftJoin('DRPAdminBundle:GlobalInstrument', 'gI', "WITH", "properties.instrument_type=gI.id")
				->where('properties.registration_type=:type')
				->setParameter('type',$code)
				->orderBy('properties.id','DESC')
				->getQuery()
				->getArrayResult();
			//echo"<pre>";print_r($properties);die;
				$arrAllProperties = array();
				foreach($properties as $property)
				{

				$arrProperty = $property;

				$arrProperty['grantorFN'] = '';
				$arrProperty['grantorLN'] = '';
				$arrProperty['grantortel1'] = '';
				$arrProperty['grantorNin'] = '';
				

				$arrProperty['granteeFN'] = '';
				$arrProperty['granteeLN'] = '';
				$arrProperty['granteetel1'] = '';
				$arrProperty['granteeNin'] = '';
				
				$arrProperty['partyRegistringFN'] = '';
				$arrProperty['partyRegistringLN'] = '';
				$arrProperty['partyRegistringNin'] = '';


				$arrProperty['recipientFN'] = '';
				$arrProperty['recipientLN'] = '';
				$arrProperty['recipientNin'] = '';

				$propertyDetail = $em->createQueryBuilder()
				->select('propertyDetail')
				->from('DRPAdminBundle:Company',  'propertyDetail')
				->where('propertyDetail.book_id=:bookId')
				->setParameter('bookId',$property['id'])
				->getQuery()
				->getArrayResult();
				//echo"<pre>";print_r($propertyDetail);die;

				foreach($propertyDetail as $propertyObject)
				{
					if($propertyObject['type']=='GR' )
					{
						$grantorFN = $propertyObject['first_name'];
						$grantorLN = $propertyObject['last_name'];
						$grantortel1 = $propertyObject['telephone1'];
						$grantorNin = $propertyObject['nin'];
					}
						//echo $lessorFN;die;
						
					if($propertyObject['type']=='GE' )
					{
						$granteeFN = $propertyObject['first_name'];
						$granteeLN = $propertyObject['last_name'];
						$granteetel1 = $propertyObject['telephone1'];
						$granteeNin = $propertyObject['nin'];
					}

					if($propertyObject['type']=='PR' )
					{
						$partyRegistringFN = $propertyObject['first_name'];
						$partyRegistringLN = $propertyObject['last_name'];
						$partyRegistringNin = $propertyObject['nin'];
					}
					if($propertyObject['type']=='RP' )
					{
						$recipientFN = $propertyObject['first_name'];
						$recipientLN = $propertyObject['last_name'];
						$recipientNin = $propertyObject['nin'];	
					}
					
				}
				$arrProperty['grantorFN'] = $grantorFN;
				$arrProperty['grantorLN'] = $grantorLN;
				$arrProperty['grantortel1'] = $grantortel1;
				$arrProperty['grantorNin'] = $grantorNin;

				$arrProperty['granteeFN'] = $granteeFN;
				$arrProperty['granteeLN'] = $granteeLN;
				$arrProperty['granteetel1'] = $granteetel1;
				$arrProperty['granteeNin'] = $granteeNin;

				$arrProperty['partyRegistringFN'] = $partyRegistringFN;
				$arrProperty['partyRegistringLN'] = $partyRegistringLN;
				$arrProperty['partyRegistringNin'] = $partyRegistringNin;				

				$arrProperty['recipientFN'] = $recipientFN;
				$arrProperty['recipientLN'] = $recipientLN;
				$arrProperty['recipientNin'] = $recipientNin;


				//echo"<pre>";print_r($arrProperty);die;

				array_push($arrAllProperties, $arrProperty);

			}
				$typeR = $em->getRepository('DRPAdminBundle:RegistrationType')->findOneBy(array('code'=>$code));
				
				


				$html = '';
				$html.= '<img style="width:20%;margin-left:38%;"src='.$url.'><br>';
				$html.='<h3 style="text-align:center;">Records of '.$typeR->type.' </h3>';
				$html.=  $this->render('DRPAdminBundle:Pages:drb.html.twig',array('properties'=>$arrAllProperties,'code'=>$code));
$html.= '<style>.navbar-collapse.collapse {
   				 display: hidden !important;
  
				}
				.page-header.navbar.navbar-fixed-top {
				    display: none;
				    z-index: 9995;
				}.btn-group.pull-right
				{
				display: none !important;
				}
				.modal-dialog
				{
				display: none !important;
				}.action
				{
				display:none;
				}.col-md-6 {
				    display: none !important;
				}
				.portlet-title
				{
				display:none !important;
				}.portlet-body.port {
				    display: none;
				}
				.logo-default {
				    display: none;
				}
				.pdf_Action
				{

					display:none !important;
				}
				table.table-bordered.dataTable {
				    border-collapse: separate !important;
				}
				.table-scrollable > .table-bordered {
				    border: 0 none;
				}
				.table-scrollable > .table {
				    background-color: #fff;
				    margin: 0 !important;
				    width: 100% !important;
				}
				table.dataTable {
				    clear: both;
				    margin-bottom: 6px !important;
				    margin-top: 6px !important;
				    max-width: none !important;
				}
				.dataTable {
				    clear: both;
				    margin-top: 5px;
				    width: 100%;
				}
				.table-bordered {
				    border: 1px solid #ddd;
				}
				.table {
				    margin-bottom: 20px;
				    max-width: 100%;
				    width: 100%;
				}
				table {
				    background-color: transparent;
				}
				table {
				    border-collapse: collapse;
				    border-spacing: 0;
				}.table-striped > tbody > tr:nth-of-type(2n+1) {
				    background-color: #f9f9f9;
				}.table-scrollable > .table-bordered > thead > tr > th:first-child, .table-scrollable > .table-bordered > tbody > tr > th:first-child, .table-scrollable > .table-bordered > tfoot > tr > th:first-child, .table-scrollable > .table-bordered > thead > tr > td:first-child, .table-scrollable > .table-bordered > tbody > tr > td:first-child, .table-scrollable > .table-bordered > tfoot > tr > td:first-child {
				    border-left: 0 none;
				}table.table-bordered tbody th, table.table-bordered tbody td {
				    border-bottom-width: 0;
				    border-left-width: 0;
				}
				.table-bordered > tbody > tr > td, .table-bordered > tbody > tr > th, .table-bordered > tfoot > tr > td, .table-bordered > tfoot > tr > th, .table-bordered > thead > tr > td, .table-bordered > thead > tr > th {
				    border: 1px solid #ddd;
				}
				.table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
				    border-top: 1px solid #ddd;
				    line-height: 1.42857;
				    padding: 8px;
				    vertical-align: top;
				}
				table.dataTable td, table.dataTable th {
				    box-sizing: content-box;
				}
				td, th {
				    padding: 0;
				      font-size:10px;!important;		
				}
			
				</style>';
   			 $dompdf = $this->get('slik_dompdf');

   
   			 $dompdf->getpdf($html);

  
   			 $dompdf->stream($typeR->type.'.pdf');

   
  			  $pdfoutput = $dompdf->output();
		}
		
/*========Pdf Code Ends Here============*/

		public function importDataAction(Request $request)
		{
			$em = $this->getDoctrine()->getEntityManager();

				$link = mysql_connect('localhost', 'root', 'root');
			if (!$link) 
			{
			    die('Could not connect: ' . mysql_error());
			}
			/*else
			{
				mysql_select_db('sample_drp');

				$queryData = "select * from Provincial_Mortgage";

				$resultData = mysql_query($queryData,$link);
		
				$arrData = array();
		
				while($rowData = mysql_fetch_Array($resultData,MYSQL_ASSOC))
				{

					//$arrData[] = $rowData; 
					//for($i=0;$i<count();$i++)
					//{
					//echo"<pre>";print_r($rowData);die;
					$propertyData = new Book;
					$propertyData->SetSerialNumber($rowData['Serial_number']);	
					$propertyData->SetReferenceNumber($rowData['ref_number']);
					$propertyData->SetExecutionDate($rowData['Date_of_execution']);
					$propertyData->SetReceiptDate($rowData['Date_of_receipt']);
					$propertyData->SetLandSituation($rowData['Situation_of_land']);
					$propertyData->SetOrNumber($rowData['O_R_Number']);
					$propertyData->SetStampDuty($rowData['Stamp_duty']);
					$propertyData->SetRegistrationType('PM');
					if($rowData['Nature_instrument'] == 'assignment')
					{
						$propertyData->SetInstrumentType('1');

					}
					if($rowData['Nature_instrument'] == 'agreement')
					{
						$propertyData->SetInstrumentType('2');

					}	

					if($rowData['Nature_instrument'] == 'assent from cadi')
					{
						$propertyData->SetInstrumentType('4');

					}	


					if($rowData['Nature_instrument'] == 'power of attorney')
					{
						$propertyData->SetInstrumentType('7');

					}	

					


					if($rowData['Nature_instrument'] == 'court order')
					{
						$propertyData->SetInstrumentType('14');

					}	


					if($rowData['Nature_instrument'] == 'partnership agreement')
					{
						$propertyData->SetInstrumentType('8');

					}	

					if($rowData['Nature_instrument'] == 'affidavit')
					{
						$propertyData->SetInstrumentType('10');

					}	


					if($rowData['Nature_instrument'] == 'declaration of title')
					{
						$propertyData->SetInstrumentType('16');

					}	


					if($rowData['Nature_instrument'] == 'Deed of gift')
					{
						$propertyData->SetInstrumentType('15');

					}	


					if($rowData['Nature_instrument'] == 'Tenancy agreement')
					{
						$propertyData->SetInstrumentType('18');

					}	


					if($rowData['Nature_instrument'] == 'discharge of mortgage')
					{
						$propertyData->SetInstrumentType('17');

					}	


					if($rowData['Nature_instrument'] == 'Mortgage')
					{
						$propertyData->SetInstrumentType('5');

					}	


					if($rowData['Nature_instrument'] == 'Deed of release')
					{
						$propertyData->SetInstrumentType('13');

					}	


					if($rowData['Nature_instrument'] == 'letters of authority')
					{
						$propertyData->SetInstrumentType('11');

					}	

					if($rowData['Nature_instrument'] == 'Transfer of shares')
					{
						$propertyData->SetInstrumentType('9');

					}	

					if($rowData['Nature_instrument'] == 'Assignment')
					{
						$propertyData->SetInstrumentType('1');

					}	

					if($rowData['Nature_instrument'] == 'lease')
					{
						$propertyData->SetInstrumentType('6');

					}		
	
					

					$em->persist($propertyData);
					$em->flush();

					$id = $propertyData->getId();
				


					$companyDataGR = new Company;
					$companyDataGR->SetFirstName($rowData['Grantor_fname']);
					$companyDataGR->SetLastName($rowData['Grantor_lname']);
					$companyDataGR->SetNin($rowData['Grantor_NIN']);
					$companyDataGR->SetDob($rowData['Grantor_DOB']);
					$companyDataGR->SetTelephone1($rowData['Grantor_tel1']);
					$companyDataGR->SetEmail($rowData['Grantor_email']);
					$companyDataGR->SetAddress($rowData['Grantor_Address']);
					$companyDataGR->SetTelephone2($rowData['Grantor_tel2']);
					$companyDataGR->SetBookId($id);

					$companyDataGR->SetType('GR');
					$em->persist($companyDataGR);
					$em->flush();
			
		

					$companyDataGE = new Company;
					$companyDataGE->SetFirstName($rowData['Grantee_fname']);
					$companyDataGE->SetLastName($rowData['Grantee_lname']);
					$companyDataGE->SetNin($rowData['Grantee_NIN']);
					$companyDataGE->SetDob($rowData['Grantee_DOB']);
					$companyDataGE->SetTelephone1($rowData['Grantee_tel1']);
					$companyDataGE->SetEmail($rowData['Grantee_email']);
					$companyDataGE->SetTelephone2($rowData['Grantee_tel2']);
					$companyDataGE->SetAddress($rowData['Grantee_address']);
					$companyDataGE->SetBookId($id);
					$companyDataGE->SetType('GE');
					$em->persist($companyDataGE);
					$em->flush();

					$companyDataPR = new Company;
					$companyDataPR->SetFirstName($rowData['PR_fname']);
					$companyDataPR->SetLastName($rowData['PR_lname']);
					$companyDataPR->SetNin($rowData['PR_NIN']);
					$companyDataPR->SetDob($rowData['PR_DOB']);
					$companyDataPR->SetTelephone1($rowData['PR_tel1']);
					$companyDataPR->SetEmail($rowData['PR_email']);
					$companyDataPR->SetTelephone2($rowData['PR_tel2']);
					$companyDataPR->SetAddress($rowData['PR_address']);
					$companyDataPR->SetBookId($id);


					$companyDataPR->SetType('PR');
					$em->persist($companyDataPR);
					$em->flush();


					$companyDataRP = new Company;
					$companyDataRP->SetFirstName($rowData['Recipient_fname']);
					$companyDataRP->SetLastName($rowData['recipient_lname']);
					$companyDataRP->SetNin($rowData['recipient_NIN']);
					$companyDataRP->SetDob($rowData['recipient_DOB']);
					$companyDataRP->SetTelephone1($rowData['recipient_tel1']);
					$companyDataRP->SetEmail($rowData['recipient_email']);
					$companyDataRP->SetTelephone2($rowData['recipient_tel2']);
					$companyDataRP->SetAddress($rowData['recipient_address']);
					$companyDataRP->SetBookId($id);
					$companyDataRP->SetType('RP');
					$em->persist($companyDataRP);
					$em->flush();


					$status = new RegistrationStatus;
					$status->SetStatus(0);
					$status->SetPropertyType('drb');
					$status->SetBookId($id);
					$em->persist($status);
					$em->flush();
				}
			}*/
			else
			{
				mysql_select_db('sample_drp');

				$queryData = "select * from Provincial_Lease";

				$resultData = mysql_query($queryData,$link);
		
				$arrData = array();
		
				while($rowData = mysql_fetch_Array($resultData,MYSQL_ASSOC))
				{

					$arrData[] = $rowData; 
					//echo"<pre>";print_r($arrData);die;
					$propertyData = new Book;
					$propertyData->SetSerialNumber($rowData['Serial_registration']);	
					//$propertyData->SetReferenceNumber($arrCells[0]);
					$propertyData->SetExecutionDate($rowData['Date_of_execution']);
					$propertyData->SetReceiptDate($rowData['Date_of_receipt']);
					$propertyData->SetLandSituation($rowData['Situation_of_land']);
					$propertyData->SetOrNumber($rowData['O_R_Number']);
					$propertyData->SetStampDuty($rowData['Stamp_duty']);
					$propertyData->SetLomp($rowData['LOMP']);
					$propertyData->SetRegistrationType('PL');
				
					$em->persist($propertyData);
					$em->flush();

					$id = $propertyData->getId();
				


					$companyDataGR = new Company;
					$companyDataGR->SetFirstName($rowData['Lessor_fname']);
					$companyDataGR->SetLastName($rowData['Lessor_lname']);
					$companyDataGR->SetNin($rowData['Lessor_NIN']);
					$companyDataGR->SetDob($rowData['Lessor_DOB']);
					$companyDataGR->SetTelephone1($rowData['Lessor_tel1']);
					$companyDataGR->SetEmail($rowData['Lessor_email']);
					$companyDataGR->SetAddress($rowData['Lessor_Address']);
					$companyDataGR->SetTelephone2($rowData['Lessor_tel2']);
					$companyDataGR->SetBookId($id);

					$companyDataGR->SetType('LR');
					$em->persist($companyDataGR);
					$em->flush();
			
		

					$companyDataGE = new Company;
					$companyDataGE->SetFirstName($rowData['Lessee_fname']);
					$companyDataGE->SetLastName($rowData['lessee_lname']);
					$companyDataGE->SetNin($rowData['Lessee_NIN']);
					$companyDataGE->SetDob($rowData['Lessee_DOB']);
					$companyDataGE->SetTelephone1($rowData['Lessee_tel1']);
					$companyDataGE->SetEmail($rowData['lessee_email']);
					$companyDataGE->SetTelephone2($rowData['Lessee_tel2']);
					$companyDataGE->SetAddress($rowData['Lessee_address']);
					$companyDataGE->SetBookId($id);
					$companyDataGE->SetType('LE');
					$em->persist($companyDataGE);
					$em->flush();

					$companyDataPR = new Company;
					$companyDataPR->SetFirstName($rowData['PR_fname']);
					$companyDataPR->SetLastName($rowData['PR_lname']);
					$companyDataPR->SetNin($rowData['PR_NIN']);
					$companyDataPR->SetDob($rowData['PR_DOB']);
					$companyDataPR->SetTelephone1($rowData['PR_tel1']);
					$companyDataPR->SetEmail($rowData['PR_email']);
					$companyDataPR->SetTelephone2($rowData['PR_tel2']);
					$companyDataPR->SetAddress($rowData['PR_address']);
					$companyDataPR->SetBookId($id);


					$companyDataPR->SetType('PR');
					$em->persist($companyDataPR);
					$em->flush();


					$companyDataRP = new Company;
					$companyDataRP->SetFirstName($rowData['Recipient_fname']);
					$companyDataRP->SetLastName($rowData['recipient_lname']);
					$companyDataRP->SetNin($rowData['recipient_NIN']);
					$companyDataRP->SetDob($rowData['recipient_DOB']);
					$companyDataRP->SetTelephone1($rowData['recipient_tel1']);
					$companyDataRP->SetEmail($rowData['recipient_email']);
					$companyDataRP->SetTelephone2($rowData['recipient_tel2']);
					$companyDataRP->SetAddress($rowData['recipient_address']);
					$companyDataRP->SetBookId($id);
					$companyDataRP->SetType('RP');
					$em->persist($companyDataRP);
					$em->flush();


					$status = new RegistrationStatus;
					$status->SetStatus(0);
					$status->SetPropertyType('lease');
					$status->SetBookId($id);
					$em->persist($status);
					$em->flush();
				}
			}
			mysql_close($link);
	


			if ($request->getMethod() == 'POST')
        		{
				if (is_uploaded_file($_FILES['file']['tmp_name'])) 
				{
					//readfile($_FILES['file']['tmp_name']);
				}
				$i=0;
				$handle = fopen($_FILES['file']['tmp_name'], "r");
				while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) 
				{
					
					if($i>2)
					{

						

						$arrCells = explode(',', $data[0]);
						echo"<pre>";print_r($arrCells);die;
						

						

					

					}
					$i++;
				}
			

			}
			
			return $this->render('DRPAdminBundle:Pages:importData.html.twig');
		}






}
