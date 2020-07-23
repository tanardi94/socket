<?php

define("FEED_APP_NUM", 16);
define("POPULAR_PRODUCTS_NUM", 12);
define("RECOMMENDATION_PRODUCTS_NUM", 12);
define("OTHER_PRODUCTS_NUM", 9);
define("DEFAULT_CURRENCYACCOUNT", "Rp");
define("ACCOUNTNAMELENGTHMIN", 4);
define("ACCOUNTNAMELENGTH", 30);
define("ACCOUNTUSERNAMELENGTHMIN", 4);
define("ACCOUNTUSERNAMELENGTH", 30);
define("ACCOUNTEMAILLENGTHMIN", 4);
define("ACCOUNTEMAILLENGTH", 80);
define("ADDRESSNAMELENGTHMIN", 4);
define("ADDRESSNAMELENGTH", 50);
define("ADDRESSADDRESSLENGTHMIN", 4);
define("ADDRESSADDRESSLENGTH", 100);
define("ADDRESSPHONELENGTHMIN", 10);
define("ADDRESSPHONELENGTH", 20);
define("OLDPASSWORDLENGTHMIN", 4);
define("NEWPASSWORDLENGTHMIN", 4);
define("ADMINEMAILACCOUNT", "junaedi.widjojo@gmail.com");
define("ADMINEMAILACCOUNTB", "buyz91@gmail.com");
define("ADMINEMAILACCOUNTC", "botochi21@gmail.com");
define("ACCOUNT_HELP_SHOP", 107);
define("ACCOUNT_HELP_APP", 108);
define("ACCOUNT_HELP_FAQ", 109);

class DB_Functions_Account {
 
    private $conn;	
	private $user;
	private $token;
    // constructor
    function __construct($token) {
        require_once '../include/DB_Connect.php';
        // connecting to database
        $db = new Db_Connect();
        $this->conn = $db->connect();		
		
		require_once '../include/DB_Auth.php';		
		$this->user = DB_Auth::getUser($token, $this->conn);		
		$this->token = $token;
		
		require_once '../include/Functions.php';				
    }
 
    // destructor
    function __destruct() {
         
    }
	
	function getProfile($appId = -1){		
		$profile = array();
		$stmt = $this->conn->prepare("SELECT users.name, users.username, users.bio, users.photo, users.phone, if(premium_date=null,0,if(curdate()<=date(premium_date),1,0)) premium_flag, date_format(premium_date,'%e %b %Y') premium_date, if(silver_date=null,0,if(curdate()<=date(silver_date),1,0)) silver_flag, date_format(silver_date,'%e %b %Y') silver_date, if(gold_date=null,0,if(curdate()<=date(gold_date),1,0)) gold_flag, date_format(gold_date,'%e %b %Y') gold_date, if(platinum_date=null,0,if(curdate()<=date(platinum_date),1,0)) platinum_flag, date_format(platinum_date,'%e %b %Y') platinum_date, '".DEFAULT_CURRENCYACCOUNT."' currency, users.verified_email, users.verified_phone, users.driver_status, users.email, users.subscribe, users.driver_status, users.partner_status FROM users WHERE users.status = 10 AND users.id = ?");
		$stmt->bind_param("i", $this->user['id']);
		$result = $stmt->execute();
		if ($result) {
			$profile = $stmt->get_result()->fetch_assoc();			
			$stmt->close();								
		}
		
		$arrResult = array();					
		$stmt = $this->conn->prepare("SELECT view_uid, name, icon FROM app WHERE status=1 AND owner = ? ORDER BY id ASC");		
		$stmt->bind_param("i", $this->user['id']);
		if ($stmt->execute()) {            
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        
				$arrResult[] = $r;
			}
			$stmt->close();            				
		}
		$profile['apps'] = $arrResult;
		
		if($appId!=-1){
			$stmt = $this->conn->prepare("SELECT app.id, app.balance_flag, app.name, app.balance_customer_flag, app.need_verified_email, app.driver_points_flag, ase.need_verified_phone FROM app left join app_setting ase on ase.status=1 and ase.app_id=app.id WHERE app.status=1 AND app.id = ?");		
			$stmt->bind_param("i", $appId);
			if ($stmt->execute()) {            				
				$app = $stmt->get_result()->fetch_assoc();					
				$stmt->close();
				if($app['balance_flag']==1 && ($profile['driver_status']==2 || $app['balance_customer_flag']==1)){																	
					$stmt = $this->conn->prepare("select ifnull(sum(balance.debit),0)-ifnull(sum(balance.credit),0) balance from balance where balance.account_id=1 AND balance.app_id=? AND balance.user_id=?");		
					$stmt->bind_param("ii", $app['id'], $this->user['id']);
					if ($stmt->execute()) {            				
						$profile['balance_total'] = $stmt->get_result()->fetch_assoc()['balance'];						
						$stmt->close();
					}
					$profile['balance_flag'] = $app['balance_flag'];					
				} else {
					$profile['balance_flag'] = 0;					
				}	
				if($app['driver_points_flag']==1 && $profile['driver_status']==2){																	
					$stmt = $this->conn->prepare("select ifnull(sum(driver_points.points),0) driver_points from driver_points where driver_points.user_id=? and flag=0 and status=1");		
					$stmt->bind_param("i", $this->user['id']);
					if ($stmt->execute()) {            				
						$profile['driver_points'] = $stmt->get_result()->fetch_assoc()['driver_points'];						
						$stmt->close();
					}
					$profile['driver_points_flag'] = $app['driver_points_flag'];					
				} else {
					$profile['driver_points_flag'] = 0;					
				}	
				if($profile['driver_status']==2){
					$stmt = $this->conn->prepare("select (select avg(driver_review.rating) from driver_review where driver_review.status=1 and driver_review.app_id=users.app_id and driver_review.driver_id=users.id) rating, (select count(*) cnt from order_header where order_header.status=1 and order_header.supplier_id=users.app_id and order_header.driver_assigned=users.id and order_status=4 and driver_flag=1) cnt from users where users.app_id=? and users.id=?");		
					$stmt->bind_param("ii", $app['id'], $this->user['id']);
					if ($stmt->execute()) {       
						$r = $stmt->get_result()->fetch_assoc();
						$profile['driver_rating'] = $r['rating'];						
						$profile['driver_order_finish'] = $r['cnt'];						
						$stmt->close();
					}										
				}
				$profile['app_name'] = $app['name'];
				$profile['need_verified_email'] = $app['need_verified_email'];
				$profile['need_verified_phone'] = $app['need_verified_phone'];
			}
		} else {
			$profile['balance'] = $this->getTotalBalance()['total_balance'];
		}
		return $profile;		
	}
	
	public function getDiscussion(){					
		$discussion = array();
		
		// get order discussion
		/*$stmt = $this->conn->prepare("SELECT list.title list_title, list.image list_image, list.view_uid list_view_uid, ld_parent.view_uid, ld.comment, ld.from_type type, if(ld.from_type=1,(select app.icon from app where app.id=ld.from_id),(select users.photo from users where users.id=ld.from_id)) photo, if(ld.from_type=1,(select app.name from app where app.id=ld.from_id),(select users.name from users where users.id=ld.from_id)) name, ld.creation_date
		FROM (
		SELECT parent.parent_id, max((select max(ldv.id) from list_discussion ldv where (ldv.level=2 and ldv.parent_id=parent.parent_id) or (ldv.level=1 and ldv.id=parent.parent_id))) last_id
		FROM (
		SELECT list_discussion.id, (if(list_discussion.level=1,list_discussion.id,list_discussion.parent_id)) parent_id
		FROM list_discussion
		WHERE if(list_discussion.from_type=1,(select app.owner from app where app.id=list_discussion.from_id),list_discussion.from_id)=? or (select app.owner from app,component,list where app.id=component.app_id and component.id=list.component_id and list.id=list_discussion.list_id)=?
		) parent
		GROUP BY parent.parent_id
		) discussion, list_discussion ld, list_discussion ld_parent, list
		WHERE ld.id = discussion.last_id and ld_parent.list_id = list.id and ld_parent.id = if(ld.level=1,ld.id,ld.parent_id)
		ORDER BY ld.id desc
		LIMIT 50;
");

		$stmt->bind_param("ii", $this->user['id'], $this->user['id']);*/
		
		$stmt = $this->conn->prepare("SELECT list.title list_title, list.image list_image, list.view_uid list_view_uid, ld_parent.view_uid, ld.comment, ld.from_type type, if(ld.from_type=1,(select app.icon from app where app.id=ld.from_id),(select users.photo from users where users.id=ld.from_id)) photo, if(ld.from_type=1,(select app.name from app where app.id=ld.from_id),(select users.name from users where users.id=ld.from_id)) name, ld.creation_date
FROM (SELECT ifnull(ld.parent_id, ld.id) parent_id, max(ld.id) last_id
FROM list_discussion ld, app, component, list
WHERE app.id=component.app_id and component.id=list.component_id and list.id=ld.list_id and app.owner=? and app.status=1 and component.status=1 and list.status=1 and ld.status=1
group by parent_id
order by last_id desc limit 50) discussion, list_discussion ld, list_discussion ld_parent, list
WHERE ld.id = discussion.last_id and ld_parent.list_id = list.id and ld_parent.id = if(ld.level=1,ld.id,ld.parent_id)
ORDER BY ld.id desc
");

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$discussion[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $discussion;
	}
	
	public function getReview(){					
		$review = array();
		
		// get order review
		$stmt = $this->conn->prepare("SELECT order_header.order_no title, app.icon photo, order_header.unique_id order_unique_id, list_review.comment, list_review.rating, users.name, list_review.creation_date
		FROM list_review, order_header, order_line, app, users
		WHERE app.owner=? AND app.status=1 AND list_review.status=1 AND order_header.status=1 AND order_line.status=1 AND app.id=order_header.supplier_id AND order_header.id=order_line.header_id AND list_review.user_id=users.id AND list_review.order_line_id = order_line.id
		GROUP BY order_header.order_no, app.icon, order_header.unique_id, list_review.comment, list_review.rating, users.name, list_review.creation_date
		ORDER BY list_review.creation_date desc
		LIMIT 50;
");

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$review[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $review;
	}
	
	public function getComplain(){					
		$complain = array();
		
		// get order complain
		$stmt = $this->conn->prepare("SELECT order_header.order_no title, app.icon photo, order_header.unique_id order_unique_id, order_complain.comment, users.view_uid users_view_uid, users.name, order_complain.creation_date
		FROM order_complain, order_header, app, users
		WHERE app.owner=? AND app.status=1 AND order_complain.status=1 AND order_header.status=1 AND app.id=order_header.supplier_id AND order_complain.user_id=users.id AND order_complain.order_id = order_header.id		
		ORDER BY order_complain.id desc
		LIMIT 50;
");

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$complain[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $complain;
	}
		
	public function getOrderShopping(){					
		$order = array();
		
		// get order shopping
		$stmt = $this->conn->prepare("select order_header.order_no, order_header.order_status, order_header.unique_id, DATE_FORMAT(order_header.creation_date, '%e %M %Y') creation_date, app.icon supplier_icon, app.name supplier_name, order_header.category from order_header, app where order_header.supplier_id = app.id and order_header.status=1 and order_header.customer_id = ? order by order_header.id desc limit 100;");

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$order[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $order;
	}
	
	public function getOrderBalance($appId){					
		$order = array();
		
		// get order shopping
		$stmt = $this->conn->prepare("select DATE_FORMAT(creation_date, '%d-%m-%y %H:%i') creation_date, category, ifnull(balance.debit,0)-ifnull(balance.credit,0) amount, (case when category=1 then CONCAT('Komisi Driver untuk pesanan No ',(select order_header.order_no from order_header where order_header.id=balance.attr1)) when category=2 then CONCAT('Perubahan oleh Admin',IF(LENGTH(balance.attr1)>0, CONCAT('\n(',balance.attr1, ')'),'')) when category=0 then 'Top Up' when category=3 then CONCAT('Pembayaran untuk pesanan No ',(select order_header.order_no from order_header where order_header.id=balance.attr1)) when category=4 then CONCAT('Tarik saldo pesanan No ',(select order_header.order_no from order_header where order_header.id=balance.attr1)) when category=5 then CONCAT('Pembayaran Mitra untuk pesanan No ',(select order_header.order_no from order_header where order_header.id=balance.attr1)) when category=6 then CONCAT('Promo untuk pesanan No ',(select order_header.order_no from order_header where order_header.id=balance.attr1)) when category=7 then CONCAT('Komisi Mitra untuk pesanan No ',(select order_header.order_no from order_header where order_header.id=balance.attr1)) when category=8 then CONCAT('Poin Driver [',attr2,']') when category=9 then CONCAT('Perubahan oleh Sistem',IF(LENGTH(balance.attr1)>0, CONCAT('\n(',balance.attr1, ')'),'')) else null end) note, '".DEFAULT_CURRENCYACCOUNT."' currency from balance where balance.status=1 AND balance.account_id=1 AND balance.app_id=? AND balance.user_id=? order by balance.id desc, balance.id desc limit 50");

		$stmt->bind_param("ii", $appId, $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$order[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $order;
	}
	
	public function checkEligibleBalance(){
		$stmt = $this->conn->prepare("SELECT id, balance_flag, balance_driver_commission, balance_driver_min_order FROM app WHERE status=1 AND id = ?");		
		$stmt->bind_param("i", $this->user['app_id']);
		if ($stmt->execute()) {            				
			$app = $stmt->get_result()->fetch_assoc();					
			$stmt->close();
			if($app['balance_flag']==1){																	
				$stmt = $this->conn->prepare("select ifnull(sum(balance.debit),0)-ifnull(sum(balance.credit),0) balance from balance where balance.account_id=1 AND balance.app_id=? AND balance.user_id=?");		
				$stmt->bind_param("ii", $app['id'], $this->user['id']);
				if ($stmt->execute()) {            				
					$balance = $stmt->get_result()->fetch_assoc()['balance'];
					if($balance<$app['balance_driver_min_order']){
						return false;
					}
				}
			}					
		}
		return true;
	}
	
	public function notEligibleBalance($message){
		$stmt = $this->conn->prepare("SELECT id, balance_flag, balance_driver_commission, balance_driver_min_order FROM app WHERE status=1 AND id = ?");		
		$stmt->bind_param("i", $this->user['app_id']);
		if ($stmt->execute()) {            				
			$app = $stmt->get_result()->fetch_assoc();					
			$stmt->close();
		}
		$response["error"] = TRUE;
		$response["error_msg"] = $message ." Rp ". number_format($app['balance_driver_min_order']) . "\nSilahkan lakukan Top Up terlebih dahulu";
		echo json_encode($response);
		exit;
	}
	
	public function getOrderDriverWaiting(){					
		$order = array();
		
		// get order shopping
		$stmt = $this->conn->prepare("select order_header.order_no, order_header.order_status, order_header.unique_id, order_header.creation_date, app.icon supplier_icon, app.name supplier_name, order_header.total+order_header.confirmation_code+order_header.driver_adjust_price total_price, '".DEFAULT_CURRENCYACCOUNT."' currency from order_header, app, users driver, order_line ol where driver.id = ? and order_header.supplier_id = app.id and order_header.status=1 and order_header.order_status=0 and order_header.driver_flag=1 AND order_header.courrier_type = driver.driver_type and order_header.driver_assigned is null and order_header.supplier_id = driver.app_id AND app.driver_waiting_flag=1 AND driver.driver_order=1 AND ((ST_Distance_Sphere(point(driver.driver_lng, driver.driver_lat), point(ol.origin_lng, ol.origin_lat))) * .000621371192 * 1.60934)<app.driver_waiting_radius AND order_header.id=ol.header_id and ol.category=2 and ol.service_id=-1
		order by order_header.id desc limit 100;");

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$order[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $order;
	}
	
	public function getOrderDriverHistory(){					
		$order = array();
		
		// get order shopping
		$stmt = $this->conn->prepare("select order_header.order_no, order_header.order_status, order_header.unique_id, order_header.creation_date, app.icon supplier_icon, app.name supplier_name, order_header.total+order_header.confirmation_code+driver_adjust_price total_price, '".DEFAULT_CURRENCYACCOUNT."' currency from order_header, app where order_header.supplier_id = app.id and order_header.status=1 and order_header.order_status=4 and order_header.driver_flag=1 AND order_header.driver_assigned =?
		order by order_header.id desc limit 50;");

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$order[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $order;
	}
	
	public function getTotalBalance($withdrawable=-1) {
        
        //$stmt = $this->conn->prepare("SELECT ifnull(sum(ifnull(total,0)+(ifnull(discount,0)*-1)),0)+ifnull((select sum(ifnull(membership_referral.total,0)) from membership_referral where membership_referral.withdrawal_flag=1 and membership_referral.user_id=?),0) total_balance, '".DEFAULT_CURRENCYACCOUNT."' currency FROM order_header WHERE supplier_id IN (SELECT app.id FROM app WHERE app.owner = ?) AND withdrawal_flag=1");
		if($withdrawable>-1){
			$stmt = $this->conn->prepare("select ifnull(sum(j.credit-j.debit),0) total_balance, '".DEFAULT_CURRENCYACCOUNT."' currency from journal j, journal_batch jb where jb.status=1 and j.status=1 and jb.id=j.batch_id and j.user_id=? and j.account_id=1 and j.withdrawable=?");
			$stmt->bind_param("ii", $this->user['id'], $withdrawable);
		} else {
			$stmt = $this->conn->prepare("select ifnull(sum(j.credit-j.debit),0) total_balance, '".DEFAULT_CURRENCYACCOUNT."' currency from journal j, journal_batch jb where jb.status=1 and j.status=1 and jb.id=j.batch_id and j.user_id=? and j.account_id=1");
			$stmt->bind_param("i", $this->user['id']);

		}
 
        if ($stmt->execute()) {
            $order = array();
            $result = $stmt->get_result()->fetch_assoc();            
            $stmt->close();

            return $result;
        } else {
            return false;
        }
    }
	
	public function getBank() {
        
        $stmt = $this->conn->prepare("SELECT bank_account_name, bank_account_number FROM users_address WHERE status=1 and user_id=?");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $order = array();
            $result = $stmt->get_result()->fetch_assoc();            
            $stmt->close();

            return $result;
        } else {
            return false;
        }
    }
	
	public function getOrderSales($isAdmin = 0){					
		$order = array();
		
		// get order shopping
		if($isAdmin==1){
		$stmt = $this->conn->prepare("select order_header.order_no, order_header.order_status, order_header.unique_id, order_header.creation_date, app.icon supplier_icon, users.name customer_name, order_header.category from order_header, app, users where order_header.supplier_id = app.id and order_header.status=1 and app.id = (select admin.app_id from users admin where admin.id=?) and users.id = order_header.customer_id order by order_header.id desc limit 100;");
		} else {
		$stmt = $this->conn->prepare("select order_header.order_no, order_header.order_status, order_header.unique_id, order_header.creation_date, app.icon supplier_icon, users.name customer_name, order_header.category from order_header, app, users where order_header.supplier_id = app.id and order_header.status=1 and app.owner = ? and users.id = order_header.customer_id order by order_header.id desc limit 100;");
		}

		$stmt->bind_param("i", $this->user['id']);
		
		if ($stmt->execute()) {				
			$result = $stmt->get_result();
			while($r = $result->fetch_assoc()) {                                        										
				$order[] = $r;
			}            
			$stmt->close();															
		} 
					
		
		return $order;
	}
		
	function getProfileView(){				
		$stmt = $this->conn->prepare("SELECT users.name, users.username, users.bio, users.photo, users.email, users.phone, (SELECT count(*) cnt FROM users_username uu WHERE uu.user_id = users.id AND uu.status=1 AND date(uu.creation_date)=date(now())) change_username, users.subscribe FROM users WHERE users.status = 10 AND users.id = ?");
		$stmt->bind_param("i", $this->user['id']);
		$result = $stmt->execute();
		if ($result) {
			$profile = $stmt->get_result()->fetch_assoc();			
			$stmt->close();		
			return $profile;				
		} else {
			return false;
		}		
			
	}
	
	function getDriverView(){	
		$orders = array();
		
		$stmt = $this->conn->prepare("SELECT driver_status, driver_license_plate, driver_model, driver_order, driver_type FROM users WHERE users.status = 10 AND users.id = ?");
		$stmt->bind_param("i", $this->user['id']);
		$result = $stmt->execute();
		if ($result) {
			$profile = $stmt->get_result()->fetch_assoc();				
			$stmt->close();		
			$stmt = $this->conn->prepare("SELECT order_header.unique_id, order_header.order_no FROM order_header WHERE driver_flag=1 AND order_status=0 AND driver_assigned = ? order by id desc");
			$stmt->bind_param("i", $this->user['id']);
			$result = $stmt->execute();
			if ($result) {
				$result = $stmt->get_result();
				while($r = $result->fetch_assoc()) {                                        										
					$orders[] = $r;
					$profile['order'] = $r;
				}            
			}
			$stmt->close();		
			$profile['orders'] = $orders;
			return $profile;				
		} else {
			return false;
		}		
			
	}
	
	function getPartnerView(){				
		$stmt = $this->conn->prepare("SELECT partner_status, partner_order, (select app.discount_flag from app where app.id=users.app_id) discount_flag FROM users WHERE users.status = 10 AND users.id = ?");
		$stmt->bind_param("i", $this->user['id']);
		$result = $stmt->execute();
		if ($result) {
			$profile = $stmt->get_result()->fetch_assoc();				
			$stmt->close();					
			
			return $profile;				
		} else {
			return false;
		}		
			
	}
	
	public function changeUsername($userId, $username, $appId = -1) {	
			// check one day		
        $stmt = $this->conn->prepare("SELECT count(*) cnt FROM users_username WHERE user_id = ? AND status=1 AND date(creation_date)=date(now()) AND app_id=?");
 
        $stmt->bind_param("ii", $userId, $appId);
 
        if ($stmt->execute()) {
            $count = $stmt->get_result()->fetch_assoc();
            $stmt->close();
 
			if($count['cnt']==0){
				$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users_username WHERE status=1 AND username=? AND app_id=?"); 
				$stmt->bind_param("si", $username, $appId);
		 
				if ($stmt->execute()) {
					$count = $stmt->get_result()->fetch_assoc();
					$stmt->close();
					if($count['cnt']==0){
						$stmt = $this->conn->prepare("UPDATE users SET username = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?");     
						$stmt->bind_param("sii", $username, $userId, $userId);
						$result = $stmt->execute();
						$stmt->close();
						
						// check for successful update
						if ($result) {
							$stmt = $this->conn->prepare("INSERT INTO users_username (user_id, username, creation_date, created_by, last_update_date, last_updated_by, app_id) VALUES (?, ?, NOW(), ?, NOW(), ?, ?);");
							$stmt->bind_param("isiii", $userId, $username, $userId, $userId, $appId);
							$stmt->execute();
							$stmt->close();
							       
							return true;
						} else {
							$response["error"] = FALSE;
							return $response;
						}
					} else {
						$response["error"] = TRUE;
						$response["error_msg"] = "Username sudah ada yang menggunakan";
						return $response;
					}
				} else {
					$response["error"] = TRUE;
					$response["error_msg"] = "Error2";
					return $response;
				}
			} else {
				$response["error"] = TRUE;
				$response["error_msg"] = "Kamu sudah mengganti username hari ini!";
				return $response;
			}            
        } else {
            $response["error"] = TRUE;
			$response["error_msg"] = "Error3";
			return $response;
        }
    }
	
	function updateProfile($email, $username, $name, $bio, $subscribe, $phone = null, $appId=-1){
		$username = Functions::clean($username);
		if($username != null && $this->validateProfile($email, $username, $name, $bio)){
			if(strlen($username)<4){
				$response["error"] = TRUE;
				$response["error_msg"] = "Username harus lebih atau sama dengan 4 karakter";
				return $response;
			}
			$userId = $this->user['id'];
			
			if($username!=null){
				$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users WHERE username=? AND app_id=?"); 
				$stmt->bind_param("si", $username, $appId);
		 
				if ($stmt->execute()) {
					$count = $stmt->get_result()->fetch_assoc();
					$stmt->close();
					if($count['cnt']>0){						
						$response["error"] = TRUE;
						$response["error_msg"] = "Username sudah ada yang menggunakan";
						return $response;
					}
				}
			}
			
			if($phone!=null && $this->user['phone'] != $phone){
				$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users WHERE phone=? AND app_id=?"); 
				$stmt->bind_param("si", $phone, $appId);
		 
				if ($stmt->execute()) {
					$count = $stmt->get_result()->fetch_assoc();
					$stmt->close();
					if($count['cnt']>0){						
						$response["error"] = TRUE;
						$response["error_msg"] = "No HP sudah ada yang menggunakan";
						return $response;
					}
				}
			}
			
			if(empty($this->user['username'])){
				$stmt = $this->conn->prepare("UPDATE users SET username=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("sii", $username, $userId, $userId);
				$result = $stmt->execute();
				$stmt->close();
			} else if($this->user['username'] != $username){
				$response = $this->changeUsername($this->user['id'], $username, $appId);
				if($response["error"]){
					return $response;
				}
			}
			if(empty($this->user['email'])){
				$stmt = $this->conn->prepare("UPDATE users SET email=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("sii", $email, $userId, $userId);
				$result = $stmt->execute();
				$stmt->close();
			}
			
			if(empty($this->user['phone']) || $this->user['verified_phone']==0){
				$stmt = $this->conn->prepare("UPDATE users SET last_update_date=NOW(), last_updated_by=?, phone=?, verified_phone_token=null WHERE id=?;");
				$stmt->bind_param("isi", $userId, $phone, $userId);
				$result = $stmt->execute();
				$stmt->close();
			} else if($this->user['phone']!=$phone){
				$response["error"] = TRUE;
				$response["error_msg"] = "Harap hubungi Admin untuk Mengganti No HP";
				echo json_encode($response);
			}
			
			$stmt = $this->conn->prepare("UPDATE users SET name=?, bio=?, subscribe=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("ssiii", $name, $bio, $subscribe, $userId, $userId);
				$result = $stmt->execute();
				$stmt->close();
						
			// check for successful update
			if ($result) {
				$stmt = $this->conn->prepare("SELECT users.view_uid, users.name FROM users WHERE id = ?");
				$stmt->bind_param("i", $userId);
				$stmt->execute();
				$user = $stmt->get_result()->fetch_assoc();
				$stmt->close();			
				
				$response["error"] = FALSE;
				$response["account"] = $user;
				return $response;				
			} else {
				$response["error"] = TRUE;
				$response["error_msg"] = "Proses Mengubah Profil Gagal";
				return $response;
			}
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}		
	
	}		
	
	function updateProfilev1($email, $username, $name, $phone = null, $appId=-1){
		$username = Functions::clean($username);
		$bio = null;
		if($username != null && $this->validateProfile($email, $username, $name, $bio)){
			if(strlen($username)<4){
				$response["error"] = TRUE;
				$response["error_msg"] = "Username harus lebih atau sama dengan 4 karakter";
				return $response;
			}
			$userId = $this->user['id'];
			
			if($username!=null){
				$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users WHERE username=? AND app_id=? AND id!=?"); 
				$stmt->bind_param("sii", $username, $appId, $userId);
		 
				if ($stmt->execute()) {
					$count = $stmt->get_result()->fetch_assoc();
					$stmt->close();
					if($count['cnt']>0){						
						$response["error"] = TRUE;
						$response["error_msg"] = "Username sudah ada yang menggunakan";
						return $response;
					}
				}
			}
			
			if($phone!=null && $this->user['phone'] != $phone){
				$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users WHERE phone=? AND app_id=? AND id!=?"); 
				$stmt->bind_param("sii", $phone, $appId, $userId);
		 
				if ($stmt->execute()) {
					$count = $stmt->get_result()->fetch_assoc();
					$stmt->close();
					if($count['cnt']>0){						
						$response["error"] = TRUE;
						$response["error_msg"] = "No HP sudah ada yang menggunakan";
						return $response;
					}
				}
			}
			
			if(empty($this->user['username'])){
				$stmt = $this->conn->prepare("UPDATE users SET username=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("sii", $username, $userId, $userId);
				$result = $stmt->execute();
				$stmt->close();
			} else if($this->user['username'] != $username){
				$response = $this->changeUsername($this->user['id'], $username, $appId);
				if($response["error"]){
					return $response;
				}
			}
			if(empty($this->user['email'])){
				$stmt = $this->conn->prepare("UPDATE users SET email=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("sii", $email, $userId, $userId);
				$result = $stmt->execute();
				$stmt->close();
			}
			
			if(empty($this->user['phone']) || $this->user['verified_phone']==0){
				$stmt = $this->conn->prepare("UPDATE users SET last_update_date=NOW(), last_updated_by=?, phone=?, verified_phone_token=null WHERE id=?;");
				$stmt->bind_param("isi", $userId, $phone, $userId);
				$result = $stmt->execute();
				$stmt->close();
			} else if($this->user['phone']!=$phone){
				$response["error"] = TRUE;
				$response["error_msg"] = "Harap hubungi Admin untuk Mengganti No HP";
				echo json_encode($response);
			}
			
			$stmt = $this->conn->prepare("UPDATE users SET name=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("sis", $name, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
						
			// check for successful update
			if ($result) {
				$stmt = $this->conn->prepare("SELECT users.view_uid, users.name FROM users WHERE id = ?");
				$stmt->bind_param("i", $userId);
				$stmt->execute();
				$user = $stmt->get_result()->fetch_assoc();
				$stmt->close();			
				
				$response["error"] = FALSE;
				$response["account"] = $user;
				return $response;				
			} else {
				$response["error"] = TRUE;
				$response["error_msg"] = "Proses Mengubah Profil Gagal";
				return $response;
			}
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}		
	
	}	
	
	function updateDriver($driver_status, $driver_license_plate, $driver_model, $driver_type, $appId=-1){
		$userId = $this->user['id'];
		
		if($driver_status==1 && $this->user['driver_status']==2){
			$response["error"] = TRUE;
			$response['error_msg'] = 'Kamu sudah diterima menjadi Petugas';
			echo json_encode($response);	
			exit;
		}
		
		$stmt = $this->conn->prepare("UPDATE users SET driver_status=?, driver_license_plate=?, driver_model=?, driver_type=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("issiii", $driver_status, $driver_license_plate, $driver_model, $driver_type, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {
			if($driver_status==1){
				$stmt = $this->conn->prepare("UPDATE users SET driver_date=NOW(), last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("ii", $userId, $userId);
				$stmt->execute();
				$stmt->close();
			
				$stmt = $this->conn->prepare("SELECT app.view_uid, IFNULL(aci.operator,'Driver') driver_operator FROM app LEFT JOIN app_courrier_icon aci ON aci.app_id=app.id AND aci.status=1 AND aci.courrier_type=? WHERE app.id = ?");
				$stmt->bind_param("ii", $driver_type, $appId);
				$stmt->execute();
				$app = $stmt->get_result()->fetch_assoc();
				$stmt->close();	
				
				require_once "../message/DB_Functions_Message.php";
				
				$db_msg = new DB_Functions_Message($this->token); 
				$db_msg->sendCustom(0, $this->user['view_uid'], 1, $app['view_uid'], 'Saya telah mendaftar sebagai '.$app['driver_operator'].'. Harap informasikan proses berikutnya yang harus saya lakukan.

(Anda dapat menyetujui saya di bagian pengelolaan)');
			}
		
			$stmt = $this->conn->prepare("SELECT users.view_uid, users.name FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$user = $stmt->get_result()->fetch_assoc();
			$stmt->close();			
			
			$response["error"] = FALSE;
			$response["account"] = $user;
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function cancelDriver(){
		$userId = $this->user['id'];
		
		$stmt = $this->conn->prepare("UPDATE users SET driver_status=0, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("ii", $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {
			
			$response["error"] = FALSE;			
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function updatePartner($partner_status, $appId=-1){
		$userId = $this->user['id'];
		
		if($partner_status==1 && $this->user['partner_status']==2){
			$response["error"] = TRUE;
			$response['error_msg'] = 'Kamu sudah diterima menjadi Mitra';
			echo json_encode($response);	
			exit;
		}
		
		$stmt = $this->conn->prepare("UPDATE users SET partner_status=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("iii", $partner_status, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {
			if($partner_status==1){
				$stmt = $this->conn->prepare("UPDATE users SET partner_date=NOW(), last_update_date=NOW(), last_updated_by=? WHERE id=?;");
				$stmt->bind_param("ii", $userId, $userId);
				$stmt->execute();
				$stmt->close();
			
				$stmt = $this->conn->prepare("SELECT app.view_uid FROM app WHERE id = ?");
				$stmt->bind_param("i", $appId);
				$stmt->execute();
				$app = $stmt->get_result()->fetch_assoc();
				$stmt->close();	
				
				require_once "../message/DB_Functions_Message.php";
				
				$db_msg = new DB_Functions_Message($this->token); 
				$db_msg->sendCustom(0, $this->user['view_uid'], 1, $app['view_uid'], 'Saya telah mendaftar sebagai Mitra. Harap informasikan proses berikutnya yang harus saya lakukan.

(Anda dapat menyetujui saya di bagian kelola Mitra)');
			}
		
			$stmt = $this->conn->prepare("SELECT users.view_uid, users.name FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$user = $stmt->get_result()->fetch_assoc();
			$stmt->close();			
			
			$response["error"] = FALSE;
			$response["account"] = $user;
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function cancelPartner(){
		$userId = $this->user['id'];
		
		$stmt = $this->conn->prepare("UPDATE users SET partner_status=0, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("ii", $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {
			
			$response["error"] = FALSE;			
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function updateDriverOrder($driver_order, $appId=-1){
		$userId = $this->user['id'];
		
		$stmt = $this->conn->prepare("UPDATE users SET driver_order=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("iii", $driver_order, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {			
			$stmt = $this->conn->prepare("SELECT users.view_uid, users.name FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$user = $stmt->get_result()->fetch_assoc();
			$stmt->close();			
			
			$response["error"] = FALSE;
			$response["account"] = $user;
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function updatePartnerOrder($partner_order, $appId=-1){
		$userId = $this->user['id'];
		
		$stmt = $this->conn->prepare("UPDATE users SET partner_order=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("iii", $partner_order, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {			
			$stmt = $this->conn->prepare("SELECT users.view_uid, users.name FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$user = $stmt->get_result()->fetch_assoc();
			$stmt->close();			
			
			$response["error"] = FALSE;
			$response["account"] = $user;
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function updateDriverLocation($driver_lat, $driver_lng, $appId=-1){
		$userId = $this->user['id'];
		
		$stmt = $this->conn->prepare("UPDATE users SET driver_lat=?, driver_lng=?, last_update_date=NOW(), last_updated_by=? WHERE id=?;");
			$stmt->bind_param("ssii", $driver_lat, $driver_lng, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
					
		// check for successful update
		if ($result) {			
			$stmt = $this->conn->prepare("SELECT users.view_uid, users.name, users.driver_lat, users.driver_lng FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$user = $stmt->get_result()->fetch_assoc();
			$stmt->close();			
			
			$response["error"] = FALSE;
			$response["account"] = $user;
			return $response;				
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Proses Mengubah Profil Gagal";
			return $response;
		}	
	
	}
	
	function getDriverLocation($appId=-1){
		$userId = $this->user['id'];
					
		$stmt = $this->conn->prepare("SELECT users.view_uid, users.name, users.driver_lat, users.driver_lng FROM users WHERE id = ?");
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$user = $stmt->get_result()->fetch_assoc();
		$stmt->close();			
		
		$response["error"] = FALSE;
		$response["account"] = $user;
		return $response;				
	
	
	}
	
	function withdraw($bank_account_name, $bank_account_number, $total_balance, $userId=null){				
		if($userId==null){
			$userId = $this->user['id'];
		}
		$stmt = $this->conn->prepare("UPDATE users_address SET bank_account_name=?, bank_account_number=?, last_update_date=NOW(), last_updated_by=? WHERE user_id=? and status=1;");
		$stmt->bind_param("ssii", $bank_account_name, $bank_account_number, $userId, $userId);
		$result = $stmt->execute();
		$stmt->close();
					
		// check for successful update
		if ($result) {
			include "../include/Email.php";
		
			$JJemail = new JJEmail(); 
			$JJemail->sendEmail(ADMINEMAILACCOUNT, 'Permintaan penarikan Dana', 'Permintaan dari Id ' . $userId . '<br />Sebesar '.$total_balance.' kirim ke '.$bank_account_number.' a\n ' .$bank_account_name);
			$JJemail->sendEmail(ADMINEMAILACCOUNTB, 'Permintaan penarikan Dana', 'Permintaan dari Id ' . $userId . '<br />Sebesar '.$total_balance.' kirim ke '.$bank_account_number.' a\n ' .$bank_account_name);
			$JJemail->sendEmail(ADMINEMAILACCOUNTC, 'Permintaan penarikan Dana', 'Permintaan dari Id ' . $userId . '<br />Sebesar '.$total_balance.' kirim ke '.$bank_account_number.' a\n ' .$bank_account_name);
			return true;
		} else {
			return false;
		}
	}
	
	function uploadPhoto($view_uid, $filename){
		$userId = $this->user['id'];
				
		$stmt = $this->conn->prepare("UPDATE users SET photo = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?;");
		$stmt->bind_param("sii", $filename, $userId, $userId);
		$result = $stmt->execute();
		$stmt->close();
					
		// check for successful store
		if ($result) {				
			return true;
		} else {
			return false;
		}
		
	}
	
	
	function validateProfile($email, $username, $name, $bio){		
		if(strlen($email)<ACCOUNTEMAILLENGTHMIN){
			return false;
		}
		if(strlen($email)>ACCOUNTEMAILLENGTH){
			return false;
		}
		if(strlen($username)<ACCOUNTUSERNAMELENGTHMIN){
			return false;
		}
		if(strlen($username)>ACCOUNTUSERNAMELENGTH){
			return false;
		}		
		if(strlen($name)<ACCOUNTNAMELENGTHMIN){
			return false;
		}
		if(strlen($name)>ACCOUNTNAMELENGTH){
			return false;
		}
			
		return true;
	}
	
	function viewAddress(){				
		$stmt = $this->conn->prepare("SELECT ua.full_name, ua.address, ua.province_name, ua.city_name, ua.district_name,ua.phone_number, ua.province, ua.city, ua.district, ua.bank_account_name, ua.bank_account_number, users.subscribe FROM users_address ua, users WHERE ua.status=1 AND ua.user_id=? AND users.id=ua.user_id");		
		$stmt->bind_param("i", $this->user['id']);
		if ($stmt->execute()) {            
			$result = $stmt->get_result()->fetch_assoc();						
			$stmt->close();            				
			if($result==null){
				$result = array();
				$result['full_name'] = "";
				$result['address'] = "";
				$result['province_name'] = "";
				$result['province_name'] = "";
				$result['district_name'] = "";
				$result['phone_number'] = "";
				$result['province'] = "";
				$result['city'] = "";
				$result['district'] = "";
				$result['bank_account_name'] = "";
				$result['bank_account_number'] = "";
			}
			return $result;
		}
		return false;		
	}
	
	function updateAddress($name, $address, $phone, $province, $city, $district, $bank_account_name, $bank_account_number){				
		if($this->validateAddress($name, $address, $phone, $province, $city, $district)){
			$userId = $this->user['id'];
			
			// create app_setting if not exist
			$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users_address WHERE user_id = ? AND status=1");		
			$stmt->bind_param("i", $userId);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();			
				$stmt->close();            				
				if($result['cnt']==0){
					$stmt = $this->conn->prepare("INSERT INTO users_address(user_id,creation_date,created_by,last_update_date,last_updated_by) VALUES(?,now(),?,now(),?)");
					$stmt->bind_param("iii", $userId, $userId, $userId);
					$result = $stmt->execute();
					$stmt->close();
				}
			}
			
			require_once '../rajaongkir/rajaongkir.php';
			$rajaongkir = new rajaongkir($this->token); 

			$province_name = $rajaongkir->getProvinceName($province);
			$city_name = $rajaongkir->getCityName($province, $city);
			$district_name = $rajaongkir->getDistrictName($city, $district);
			
			$stmt = $this->conn->prepare("UPDATE users_address SET full_name=?, phone_number=?, address=?, province=?, province_name=?, city=?, city_name=?, district=?, district_name=?, bank_account_name = ?, bank_account_number = ?, last_update_date=NOW(), last_updated_by=? WHERE user_id=? and status=1;");
			$stmt->bind_param("sssisisisssii", $name, $phone, $address, $province, $province_name, $city, $city_name, $district, $district_name, $bank_account_name, $bank_account_number, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
						
			// check for successful update
			if ($result) {
				$stmt = $this->conn->prepare("SELECT ua.full_name, ua.address, ua.province_name, ua.city_name, ua.district_name,ua.phone_number FROM users_address ua WHERE ua.status=1 AND ua.user_id=?");		
				$stmt->bind_param("i", $this->user['id']);
				$stmt->execute();
				$app = $stmt->get_result()->fetch_assoc();
				$stmt->close();			
				return $app;
			} else {
				return false;
			}
		}
	
	}
	
	function updateAddressv1($name, $address, $phone, $province, $city, $district, $subscribe){				
		if($this->validateAddress($name, $address, $phone, $province, $city, $district)){
			$userId = $this->user['id'];			
				
			$stmt = $this->conn->prepare("UPDATE users SET subscribe = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?;");
			$stmt->bind_param("iii", $subscribe, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
			
			// create app_setting if not exist
			$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users_address WHERE user_id = ? AND status=1");		
			$stmt->bind_param("i", $userId);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();			
				$stmt->close();            				
				if($result['cnt']==0){
					$stmt = $this->conn->prepare("INSERT INTO users_address(user_id,creation_date,created_by,last_update_date,last_updated_by) VALUES(?,now(),?,now(),?)");
					$stmt->bind_param("iii", $userId, $userId, $userId);
					$result = $stmt->execute();
					$stmt->close();
				}
			}
			
			require_once '../rajaongkir/rajaongkir.php';
			$rajaongkir = new rajaongkir($this->token); 

			$province_name = $rajaongkir->getProvinceName($province);
			$city_name = $rajaongkir->getCityName($province, $city);
			$district_name = $rajaongkir->getDistrictName($city, $district);
			
			$stmt = $this->conn->prepare("UPDATE users_address SET full_name=?, phone_number=?, address=?, province=?, province_name=?, city=?, city_name=?, district=?, district_name=?, last_update_date=NOW(), last_updated_by=? WHERE user_id=? and status=1;");
			$stmt->bind_param("sssisisisii", $name, $phone, $address, $province, $province_name, $city, $city_name, $district, $district_name, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
						
			// check for successful update
			if ($result) {
				$stmt = $this->conn->prepare("SELECT ua.full_name, ua.address, ua.province_name, ua.city_name, ua.district_name,ua.phone_number FROM users_address ua WHERE ua.status=1 AND ua.user_id=?");		
				$stmt->bind_param("i", $this->user['id']);
				$stmt->execute();
				$app = $stmt->get_result()->fetch_assoc();
				$stmt->close();			
				return $app;
			} else {
				return false;
			}
		}
	
	}
		
	function validateAddress($name, $address, $phone, $province, $city, $district){
		if($province==-1 || empty($province)){
			return false;
		}
		if($city==-1 || empty($city)){
			return false;
		}
		if($district==-1 || empty($district)){
			return false;
		}
		if(strlen($name)<ADDRESSNAMELENGTHMIN){
			return false;
		}
		if(strlen($name)>ADDRESSNAMELENGTH){
			return false;
		}
		if(strlen($address)<ADDRESSADDRESSLENGTHMIN){
			return false;
		}
		if(strlen($address)>ADDRESSADDRESSLENGTH){
			return false;
		}
		if(strlen($phone)<ADDRESSPHONELENGTHMIN){
			return false;
		}
		if(strlen($phone)>ADDRESSPHONELENGTH){
			return false;
		}
		
			
		return true;
	}
	
	function updateLanguage($token, $language){					
		$userId = $this->user['id'];
		$stmt = $this->conn->prepare("UPDATE users_token SET language=?, last_update_date=NOW(), last_updated_by=? WHERE access_token=? and status=1;");
		$stmt->bind_param("sis", $language, $userId, $token);
		$result = $stmt->execute();
		$stmt->close();		

		$stmt = $this->conn->prepare("UPDATE users SET language=?, last_update_date=NOW(), last_updated_by=? WHERE id=? and status=10;");
		$stmt->bind_param("sii", $language, $userId, $userId);
		$result = $stmt->execute();
		$stmt->close();			
	}
		
	
	function getHelpShop(){		
		$arrResult = array();			
		
		$id = ACCOUNT_HELP_SHOP;
		$stmt = $this->conn->prepare("SELECT content FROM news WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {            
            $result = $stmt->get_result()->fetch_assoc();            
            $stmt->close();
            $arrResult['content'] = $result['content'];
        } else {
            return false;
        }
			
		return $arrResult;		
	}
	
	function getHelpApp(){		
		$arrResult = array();			
		
		$id = ACCOUNT_HELP_APP;
		$stmt = $this->conn->prepare("SELECT content FROM news WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {            
            $result = $stmt->get_result()->fetch_assoc();            
            $stmt->close();
            $arrResult['content'] = $result['content'];
        } else {
            return false;
        }
			
		return $arrResult;		
	}
	
	function getHelpFaq(){		
		$arrResult = array();			
		
		$id = ACCOUNT_HELP_FAQ;
		$stmt = $this->conn->prepare("SELECT content FROM news WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {            
            $result = $stmt->get_result()->fetch_assoc();            
            $stmt->close();
            $arrResult['content'] = $result['content'];
        } else {
            return false;
        }
			
		return $arrResult;		
	}
	
	function viewPassword(){				
		$stmt = $this->conn->prepare("SELECT u.updated_password updated FROM users u WHERE u.status=10 AND u.id=?");		
		$stmt->bind_param("i", $this->user['id']);
		if ($stmt->execute()) {            
			$result = $stmt->get_result()->fetch_assoc();			
			$stmt->close();            				
			return $result;
		}
		return false;		
	}
	
	function updatePassword($updated, $old_password, $new_password){		
		if($this->validatePassword($updated, $old_password, $new_password)){
			$userId = $this->user['id'];		
			$stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
	 
			$stmt->bind_param("i", $userId);
	 
			if ($stmt->execute()) {
				$user = $stmt->get_result()->fetch_assoc();
				$stmt->close();
				$updatePassword = false;            
				if($user['updated_password']>0){
					// verifying user old password
					$salt = $user['salt'];
					$encrypted_password = $user['encrypted_password'];
					$hash = $this->checkhashSSHA($salt, $old_password);
					// check for password equality
					if ($encrypted_password == $hash) {
						$updatePassword = true;
					}
				} else {
					$updatePassword = true;
				}
				
				if($updatePassword){
					// user authentication details are correct
					$hash = $this->hashSSHA($new_password);
					$encrypted_password = $hash["encrypted"]; // encrypted password
					$salt = $hash["salt"]; // salt
			 
					$stmt = $this->conn->prepare("UPDATE users SET encrypted_password = ?, salt = ?, last_update_date = NOW(), last_updated_by = ?, updated_password = updated_password+1 WHERE id = ?");      
					$stmt->bind_param("ssii", $encrypted_password, $salt, $userId, $userId);
					$result = $stmt->execute();
					$stmt->close();
					  
					// check for successful store
					if ($result) {
						// logout all
						$stmt = $this->conn->prepare("UPDATE users_token SET last_update_date = NOW(), last_updated_by = ?, status = 0 WHERE status=1 AND user_id=? AND app_id=? AND access_token!=?");
						$stmt->bind_param("iiis", $user['id'], $user['id'], $user['app_id'], $this->token);
						$result = $stmt->execute();
						$stmt->close();

						return true;
					} else {						
						return false;
					}
				} else {
					$response["error"] = TRUE;
					$response["error_msg"] = "Password lama salah!";
					echo json_encode($response);
					exit;
					return false;
				}
			} else {
				
				return false;
			}       
		} else {		
			return false;
		}		
	
	}
	
	/**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
    public function hashSSHA($password) {
 
        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }
 
    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password) {
 
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
 
        return $hash;
    }
		
	function validatePassword($updated, $old_password, $new_password){		
		if($updated>0 && strlen($old_password)<OLDPASSWORDLENGTHMIN){
			return false;
		}
		if(strlen($new_password)<NEWPASSWORDLENGTHMIN){
			return false;
		}		
			
		return true;
	}
	
	public function getDriverGuideLink() {
 
        $stmt = $this->conn->prepare("SELECT app.driver_guide_link from app WHERE app.status = 1 and app.id = (select users.app_id from users where users.id=?)");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $app = $stmt->get_result()->fetch_assoc();                                
            $stmt->close();
 
            return $app['driver_guide_link'];
        } else {
            return false;
        }
    }
	
	public function getAppSetting() {
 
        $stmt = $this->conn->prepare("SELECT app.driver_guide_link, ase.driver_register_form_flag, form_driver.view_uid driver_register_form_view_uid, ase.partner_register_form_flag, form_partner.view_uid partner_register_form_view_uid from app left join app_setting ase on ase.app_id=app.id and ase.status=1 left join form_header form_driver on form_driver.status=1 and ase.driver_register_form_id=form_driver.id left join form_header form_partner on form_partner.status=1 and ase.partner_register_form_id=form_partner.id WHERE app.status = 1 and app.id = (select users.app_id from users where users.id=?)");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $app = $stmt->get_result()->fetch_assoc();                                
            $stmt->close();
 
            return $app;
        } else {
            return false;
        }
    }
	
	public function isCS() {
 
        $stmt = $this->conn->prepare("SELECT count(*) cnt FROM users admin, app, users owner WHERE admin.app_id=app.id AND app.owner=owner.id AND (admin.admin_status=1 OR admin.admin_status=2) AND admin.status=10 AND app.status=1 AND owner.status=10 AND app.admin_flag=1 AND if(owner.gold_date=null,0,if(curdate()<=date(owner.gold_date),1,0))=1 AND admin.id=?");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $cnt = $stmt->get_result()->fetch_assoc()['cnt'];                                
            $stmt->close();
 
            if($cnt>0){
				return true;
			} else {
				return false;
			}
        } else {
            return false;
        }
    }
	
	public function isSPV() {
 
        $stmt = $this->conn->prepare("SELECT count(*) cnt FROM users admin, app, users owner WHERE admin.app_id=app.id AND app.owner=owner.id AND admin.admin_status=2 AND admin.status=10 AND app.status=1 AND owner.status=10 AND app.admin_flag=1 AND if(owner.gold_date=null,0,if(curdate()<=date(owner.gold_date),1,0))=1 AND admin.id=?");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $cnt = $stmt->get_result()->fetch_assoc()['cnt'];                                
            $stmt->close();
 
            if($cnt>0){
				return true;
			} else {
				return false;
			}
        } else {
            return false;
        }
    }
	
	public function isDriverLocked() {
 
        $stmt = $this->conn->prepare("SELECT count(*) cnt FROM users, app left join app_setting ase on ase.app_id=app.id and ase.status=1 WHERE users.app_id=app.id AND users.id=? AND users.driver_status=2 AND ase.driver_lock_profile_flag=1");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $cnt = $stmt->get_result()->fetch_assoc()['cnt'];                                
            $stmt->close();
 
            if($cnt>0){
				return true;
			} else {
				return false;
			}
        } else {
            return false;
        }
    }
	
	public function isPartnerLocked() {
 
        $stmt = $this->conn->prepare("SELECT count(*) cnt FROM users, app left join app_setting ase on ase.app_id=app.id and ase.status=1 WHERE users.app_id=app.id AND users.id=? AND users.partner_status=2 AND ase.partner_lock_profile_flag=1");
 
        $stmt->bind_param("i", $this->user['id']);
 
        if ($stmt->execute()) {
            $cnt = $stmt->get_result()->fetch_assoc()['cnt'];                                
            $stmt->close();
 
            if($cnt>0){
				return true;
			} else {
				return false;
			}
        } else {
            return false;
        }
    }
	
	public function getAppIdByViewUId($vuid) {
 
        $stmt = $this->conn->prepare("SELECT app.id from app WHERE app.status = 1 and app.view_uid = ?");
 
        $stmt->bind_param("s", $vuid);
 
        if ($stmt->execute()) {
            $app = $stmt->get_result()->fetch_assoc();                                
            $stmt->close();
 
            return $app['id'];
        } else {
            return false;
        }
    }
	
	function checkLogin(){
		if($this->user!=false){
			return true;
		} else {
			return false;
		}
	}
}