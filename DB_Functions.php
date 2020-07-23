<?php
 
/**
 * @author Junaedi Widjojo, @reference Ravi Tamada
 * @link http://www.androidhive.info/2012/01/android-login-and-registration-with-php-mysql-and-sqlite/ Complete tutorial
 */

define("APPNAME", "Jagel.id"); 
define("HOME_RECOMMENDED_APP_NUM", 6);
define("HOME_RECOMMENDED_NEW_PRODUCTS_NUM", 8);
define("HOME_RECOMMENDED_NEW_BLOGS_NUM", 12);
define("DEFAULT_CURRENCY", "Rp");
define("ROOT", '../../');
define("PASSWORDMINLENGTH", 6);
define("USERNAMELENGTH", 30);
define("APPVERSION", 112);
define("DOWNLOADURL", "https://play.google.com/store/apps/details?id=com.jgjk.buataplikasiandroid");
define("LIMITLOGIN", 10);
define("INTERVALLOGIN", 10);
define("LIMITVALIDATE", 5);
define("INTERVALVALIDATE", 60);
define("LIMIT_MESSAGE", 20);

class DB_Functions {
 
    private $conn;	
    // constructor
    function __construct() {
        require_once 'DB_Connect.php';
        // connecting to database
        $db = new Db_Connect();
        $this->conn = $db->connect();
		
		require_once 'Functions.php';				
    }
 
    // destructor
    function __destruct() {
         
    }
	
	public function getUser($token) {
		
		require_once '../include/DB_Auth.php';		
		$user = DB_Auth::getUser($token, $this->conn);
		
		return $user;
    }
	
	public function getHome($token, $appVersion=-1) {
		
		$home['slides'] = $this->getSlides();
        $home['recommendedApps'] = $this->getRecommendedApps();
		$home['homeApps'] = $this->getHomeApps();
		$home['newProducts'] = $this->getNewProducts();
		
		if(!empty($token)){		
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);		
		} else {
			$user = null;
		}
		$home['numNotifications'] = $this->getNumNotifications($user);
		$home['numMessages'] = $this->getNumMessages($user);
		$home['numCarts'] = $this->getNumCarts($user);
		if($appVersion<APPVERSION){
			$home['app_update'] = 1;
			$home['download_url'] = DOWNLOADURL;
			$home['change_log'] = '
Aplikasi Jagel.id sudah ada yang baru loh!
- Jumlah kolom produk pada menu "Daftar Produk" bisa diubah
- Dapat menghilangkan nama menu pada layout Nav atas dan Nav bawah
- Icon pada layout grid bisa diatur dengan tampilan lebih penuh
- Sekarang bisa ganti email lewat aplikasi
- Detail pesanan akan langsung berubah saat mendapatkan driver';
		} else {
			$home['app_update'] = 0;
		}
		
		return $home;
    }
	
	public function getHomeFeed($token, $appVersion=-1, $homeVersion=-1) {
		
		$home['slides'] = $this->getSlides();
        $home['recommendedApps'] = $this->getRecommendedApps();
		$home['homeApps'] = $this->getHomeApps();
		if($homeVersion==1){
			$home['newBlogs'] = $this->getNewBlogs();
		} else {
			$home['newProducts'] = $this->getNewProducts();
		}		
		if($appVersion<APPVERSION){
			$home['app_update'] = 1;
			$home['download_url'] = DOWNLOADURL;
			$home['change_log'] = '
Aplikasi jagel.id sudah ada yang baru loh!
- Jumlah kolom produk pada menu "Daftar Produk" bisa diubah
- Dapat menghilangkan nama menu pada layout Nav atas dan Nav bawah
- Icon pada layout grid bisa diatur dengan tampilan lebih penuh
- Sekarang bisa ganti email lewat aplikasi
- Detail pesanan akan langsung berubah saat mendapatkan driver';
		} else {
			$home['app_update'] = 0;
		}
		
		return $home;
    }
	
	public function getHomeNotification($token, $appVersion=-1) {
		
		if(!empty($token)){		
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);		
			if($user["need_token"]==1){
				$home["need_firebase_token"] = true;		
			}
		} else {
			$user = null;
		}
		$home['numNotifications'] = $this->getNumNotifications($user);
		$home['numMessages'] = $this->getNumMessages($user);
		$home['numCarts'] = $this->getNumCarts($user);
		
		return $home;
    }
	
	public function getSlides(){
		$arrResult = array();

		$slider1 = array();
		$slider1['image'] = 'jagel_banner_default.png';
		$slider1['type'] = 0;
		$arrResult[] = $slider1;	
		
		$slider2 = array();
		$slider2['image'] = 'jagel_banner_waspada.png';
		$slider2['type'] = 0;
		$arrResult[] = $slider2;		
		
		return $arrResult;
	}
	
	public function getRecommendedApps(){		
		$arrResult = array();
		$num = HOME_RECOMMENDED_APP_NUM;
        $stmt = $this->conn->prepare("SELECT view_uid, name, icon FROM app WHERE status=1 AND calculated_seq>150 AND is_visible = 1 ORDER BY calculated_seq desc, id DESC LIMIT ?");		
		$stmt->bind_param("i", $num);
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {                                        
                $arrResult[] = $r;
            }
            $stmt->close();            
			return $arrResult;
        } else {
			return false;
		}
	}   

	public function getHomeApps(){		
		$arrResult = array();
		// 0 = Katalog, 1 = Company Profile, 2 = Portfolio, 3 = Online Shop
		if(isset($_POST['hl']) && $_POST['hl']=='en'){
			$templateTitle = array("Category Catalog", "Category Company Profile", "Category Portfolio", "Category Online Shop");
		} else {		
			$templateTitle = array("Kategori Katalog", "Kategori Company Profile", "Kategori Portfolio", "Kategori Online Shop");
		}
		for($i=0;$i<4;$i++){
			$arr = array();
			$arr['name'] = $templateTitle[$i];
			$arr['list'] = $this->getHomeListApps($i);
			$arrResult[] = $arr;
		}		
		return $arrResult;        
	}   
	
	public function getHomeListApps($templateType){		
		$arrResult = array();
		$num = HOME_RECOMMENDED_APP_NUM;
        $stmt = $this->conn->prepare("SELECT view_uid, name, icon FROM app WHERE status=1 AND calculated_seq>150 AND is_visible = 1 AND template = ? ORDER BY calculated_seq desc, id DESC LIMIT ?");		
		$stmt->bind_param("ii", $templateType, $num);
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {                                        
                $arrResult[] = $r;
            }
            $stmt->close();            
			return $arrResult;
        } else {
			return false;
		}
	}   
	
	public function getNewProducts(){		
		$arrResult = array();
		$num = HOME_RECOMMENDED_NEW_PRODUCTS_NUM;
		
        //$stmt = $this->conn->prepare("SELECT list.view_uid, list.title, list.image, '".DEFAULT_CURRENCY."' currency, ifnull(list.price,0) price FROM app, component, list WHERE app.status=1 AND app.is_visible = 1 AND component.status = 1 AND list.status = 1 AND app.id = component.app_id AND component.id = list.component_id AND list.purchasable = 1 ORDER BY list.id desc LIMIT ?");
		$stmt = $this->conn->prepare("SELECT list.view_uid, list.title, list.image, '".DEFAULT_CURRENCY."' currency, ifnull(list.price,0) price FROM app, component, list WHERE app.status=1 AND component.status = 1 AND list.status = 1 AND app.is_visible = 1 AND app.id = component.app_id AND component.id = list.component_id AND list.image is not null AND list.purchasable=1 AND list.type in (0,1,3) AND app.calculated_seq>150 ORDER BY list.id desc LIMIT ?");
		$stmt->bind_param("i", $num);
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {                                        
                $arrResult[] = $r;
            }
            $stmt->close();            
			return $arrResult;
        } else {
			return false;
		}
	}    
	
	public function getNewBlogs(){		
		$arrResult = array();
		$num = HOME_RECOMMENDED_NEW_BLOGS_NUM;
		
        $stmt = $this->conn->prepare("SELECT news.view_uid, news.title, news.image, CONCAT('https://jgjk.mobi/b/',news.id) url, news.content FROM news WHERE news.status=1 AND news.category=3 ORDER BY news.creation_date ASC LIMIT ?");
		$stmt->bind_param("i", $num);
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {     
				$content = trim(strip_tags($r['content']));        
				$r['content'] = substr($content,0,140) . '...';
                $arrResult[] = $r;
            }
            $stmt->close();            
			return $arrResult;
        } else {
			return false;
		}
	} 
	
	public function getNumNotifications($user){		
		$cnt = 0;
		if(!empty($user)){
			$stmt = $this->conn->prepare("SELECT count(*) cnt FROM order_header WHERE order_header.status=1 AND ((order_header.customer_id= ? AND (order_header.order_status in (1,3) or order_header.review_flag=1)) OR (order_header.supplier_id in (select app.id from app where app.owner=?) AND (order_header.order_status in (0,1,2,3) or order_header.withdrawal_flag=1)))");		
			$stmt->bind_param("ii", $user['id'], $user['id']);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();					
				$stmt->close();            
				$cnt += $result['cnt'];
			}
			
			$stmt = $this->conn->prepare("SELECT count(*) cnt FROM order_premium WHERE order_premium.status=1 AND order_premium.order_status=1 AND order_premium.user_id = ?");		
			$stmt->bind_param("i", $user['id']);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();					
				$stmt->close();            
				$cnt += $result['cnt'];
			}
			
			$stmt = $this->conn->prepare("SELECT count(*) cnt FROM order_web WHERE order_web.status=1 AND (select is_web from app where app.id = order_web.app_id)=1 AND order_web.user_id = ?");		
			$stmt->bind_param("i", $user['id']);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();					
				$stmt->close();            
				$cnt += $result['cnt'];
			}
							
			return $cnt;
		} else {
			return 0;
		}	
	}
	
	public function getNumCarts($user){		
		if(!empty($user)){
			$stmt = $this->conn->prepare("SELECT IFNULL(sum(qty),0) qty FROM cart WHERE cart.status=1 AND cart.user_id=? AND (select app.id from app,component,list where app.status=1 and component.status=1 and list.status=1 and app.id=component.app_id and component.id=list.component_id and list.id=cart.list_id) is not null");		
			$stmt->bind_param("i", $user['id']);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();					
				$stmt->close();            
				return $result['qty'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	
	public function getNumMessages($user){	
		if(!empty($user)){
			$stmt = $this->conn->prepare("select ifnull(sum(mh.unread),0) cnt from message_header mh, users where mh.from_user_id=? and mh.status=1 and mh.to_user_id=users.id and users.status=10 and mh.unread>0");		
			$stmt->bind_param("i", $user['id']);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();					
				$stmt->close();            
				return $result['cnt'];
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	
	public function getNotification($token){
		if(!empty($token)){
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){
				$notification = array();
				
				// get notification shopping
				$stmt = $this->conn->prepare("SELECT unique_id attr_link,last_update_date creation_date,(case when order_status=4 or order_status<0 then 1 else 0 end) status,order_no attr_name, order_status attr_status, 0 notification_type FROM order_header WHERE customer_id=? and order_header.status=1 and (order_header.order_status in (0,1,2,3) or order_header.review_flag=1) ORDER BY (case when order_status=4 or order_status<0 then 1 else 0 end) ASC, last_update_date DESC");
 
				$stmt->bind_param("i", $user['id']);
				
				if ($stmt->execute()) {
					$notification['shopping'] = array();
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        
						$notification['shopping'][] = $r;
					}            
					$stmt->close();															
				} 
				
				// get notification sales
				$stmt = $this->conn->prepare("SELECT unique_id attr_link,last_update_date creation_date,(case when order_status=4 or order_status<0 then 1 else 0 end) status,order_no attr_name, order_status attr_status, 1 notification_type FROM order_header WHERE supplier_id in (select app.id from app where app.owner=? and app.status=1) and (order_header.order_status in (0,1,2,3) or order_header.withdrawal_flag=1) ORDER BY (case when order_status=4 or order_status<0 then 1 else 0 end) ASC, last_update_date DESC");
 
				$stmt->bind_param("i", $user['id']);
				
				if ($stmt->execute()) {
					$notification['sales'] = array();
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        
						$notification['sales'][] = $r;
					}            
					$stmt->close();															
				} 
				
				// get notification premium
				$stmt = $this->conn->prepare("SELECT unique_id attr_link,last_update_date creation_date,(case when order_status=4 or order_status<0 then 1 else 0 end) status,type attr_name, order_status attr_status, 2 notification_type, level attr1, num attr2 FROM order_premium WHERE user_id=? and status=1 and order_status=1 ORDER BY id DESC");
 
				$stmt->bind_param("i", $user['id']);
				
				if ($stmt->execute()) {
					$notification['premium'] = array();
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        
						$notification['premium'][] = $r;
					}            
					$stmt->close();															
				} 
				
				// get notification web
				$stmt = $this->conn->prepare("SELECT unique_id attr_link,last_update_date creation_date,(case when (select app.is_web from app where app.id=order_web.app_id)=4 or (select app.is_web from app where app.id=order_web.app_id)<0 then 1 else 0 end) status,concat(domain_name,'.',domain_suffix) attr_name, (select app.is_web from app where app.id=order_web.app_id) attr_status, 3 notification_type FROM order_web WHERE user_id=? and status=1 and (select app.is_web from app where app.id=order_web.app_id)=1 ORDER BY id DESC");
 
				$stmt->bind_param("i", $user['id']);
				
				if ($stmt->execute()) {
					$notification['web'] = array();
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        
						$notification['web'][] = $r;
					}            
					$stmt->close();															
				} 
				
				// get notification topup
				$stmt = $this->conn->prepare("SELECT unique_id attr_link,last_update_date creation_date,(case when order_status=4 or order_status<0 then 1 else 0 end) status,type attr_name, order_status attr_status, 4 notification_type, total attr1, null attr2 FROM order_topup WHERE user_id=? and status=1 and order_status=1 ORDER BY id DESC");
 
				$stmt->bind_param("i", $user['id']);
				
				if ($stmt->execute()) {
					$notification['topup'] = array();
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        
						$notification['topup'][] = $r;
					}            
					$stmt->close();															
				}
				
				return $notification;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function getMessage($token){
		if(!empty($token)){
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){
				$message = array();
				
				// get message
				$stmt = $this->conn->prepare("select if(interact_type=0 and m.interact_id=1763,1,0) is_admin,if(interact_type=0,(select users.view_uid from users where users.id=m.interact_id),(select app.view_uid from app where app.id=m.interact_id)) to_id, m.interact_type to_type, sum(m.seen_flag) unread, max(m.creation_date) last_date, (select m2.content from message m2 where m2.id=max(m.id)) last_content, (select IF((m2.from_type=0 and m2.from_id=?) or (m2.from_type=1 and m2.from_id in (select app.id from app where app.owner=?)),1,0) from message m2 where m2.id=max(m.id)) role, (select IF((m2.from_type=0 and m2.from_id=?) or (m2.from_type=1 and m2.from_id in (select app.id from app where app.owner=?)),if(m2.from_type=0, (select users.view_uid from users where users.id=m2.from_id), (select app.view_uid from app where app.id=m2.from_id)),if(m2.to_type=0, (select users.view_uid from users where users.id=m2.to_id), (select app.view_uid from app where app.id=m2.to_id))) from message m2 where m2.id=max(m.id)) from_id, (select IF((m2.from_type=0 and m2.from_id=?) or (m2.from_type=1 and m2.from_id in (select app.id from app where app.owner=?)),m2.from_type,m2.to_type) from message m2 where m2.id=max(m.id)) from_type
				from (select id, creation_date, if(?=(case when from_type=0 then from_id else (select app.owner from app where app.id=from_id) end),to_id,from_id) interact_id, if(?=(case when from_type=0 then from_id else (select app.owner from app where app.id=from_id) end),to_type,from_type) interact_type, if(((to_type=0 and to_id=?) or (to_type=1 and to_id in (select app.id from app where app.status=1 and app.owner=?))) and seen_flag=0,1,0) seen_flag from message where ((to_type=0 and to_id=? and remove_to=0) or (to_type=1 and to_id in (select app.id from app where app.status=1 and app.owner=?) and remove_to=0) or (from_type=0 and from_id=? and remove_from=0) or (from_type=1 and from_id in (select app.id from app where app.status=1 and app.owner=?) and remove_from=0))) m
				group by m.interact_id, m.interact_type
				order by max(m.creation_date) desc
				limit 50;");
 
				$stmt->bind_param("iiiiiiiiiiiiii", $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']);
				
				if ($stmt->execute()) {				
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        						
						$fromContact = $this->getContact($r['from_type'], $r['from_id']);					
						$toContact = $this->getContact($r['to_type'], $r['to_id']);
						if($fromContact!=false && $toContact!=false){
							$r['from_name'] = $fromContact['name'];
							$r['from_image'] = $fromContact['image'];						
							if($fromContact['type']==1){	
								$r['app'] = array();
								$r['app']['name'] = $fromContact['name'];
								$r['app']['premium_flag'] = $fromContact['premium_flag'];
								$r['app']['header'] = $fromContact['header'];
								$r['app']['color_custom'] = $fromContact['color_custom'];
								$r['app']['color_custom_flag'] = $fromContact['color_custom_flag'];
								$r['app']['color_font'] = $fromContact['color_font'];
							}
							$r['to_name'] = $toContact['name'];
							$r['to_image'] = $toContact['image'];
							$message[] = $r;
						}						
					}            
					$stmt->close();															
				} 
							
				
				return $message;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function viewMessageHeader($token, $page){		
		$message = array();
		$message['currentPage'] = $page+1;	
		$message['numPage'] = $this->getMessageHeaderNumPage($token);	
		$message['messages'] = $this->getMessageHeader($token, $page);				
		return $message;
	}

	public function getMessageHeaderNumPage($token){
		if(!empty($token)){
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){
				
				$num = LIMIT_MESSAGE;		
		
				$stmt = $this->conn->prepare("SELECT count(*) cnt FROM message_header mh WHERE mh.from_user_id=?");		
				$stmt->bind_param("i", $user['id']);	
				if ($stmt->execute()) {            
					$cnt = $stmt->get_result()->fetch_assoc()['cnt'];			
					$stmt->close();            							
				}
				
				$numPage = ceil($cnt / $num);
				return $numPage;								 														
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function getMessageHeader($token, $page=0){
		if(!empty($token)){
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){
				$message = array();
				
				$num = LIMIT_MESSAGE;
				$offset = $page*$num;

				// get message
				$stmt = $this->conn->prepare("SELECT if(msum.to_type=0 AND msum.to_id=1763,1,0) is_admin, if(msum.to_type=0,(select users.view_uid from users where users.id=msum.to_id),(select app.view_uid from app where app.id=msum.to_id)) to_id, msum.to_type, if(msum.from_type=0,(select users.view_uid from users where users.id=msum.from_id),(select app.view_uid from app where app.id=msum.from_id)) from_id, msum.from_type, msum.creation_date last_date, if((msum.role=1 AND msum.remove_from=0) or (msum.role=0 AND msum.remove_to=0),(case when msum.type=1 then 'Mengirim Foto untuk Anda' when msum.type=2 then 'Mengirim File untuk Anda' else msum.content end),null) last_content, msum.role, msum.unread FROM (SELECT m.content, mh.to_id, mh.to_type, mh.from_id, mh.from_type, m.creation_date, IF(mh.from_type=m.from_type AND mh.to_type=m.to_type,1,0) role, m.remove_from, m.remove_to, m.type, mh.unread FROM message_header mh, message m where mh.status=1 AND mh.from_user_id=? AND mh.last_message_id = m.id
				ORDER BY mh.last_message_id DESC LIMIT ? OFFSET ?) msum;");
 
				$stmt->bind_param("iii", $user['id'], $num, $offset);
				
				if ($stmt->execute()) {				
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        						
						$fromContact = $this->getContact($r['from_type'], $r['from_id']);					
						$toContact = $this->getContact($r['to_type'], $r['to_id']);
						if($fromContact!=false && $toContact!=false){
							$r['from_name'] = $fromContact['name'];
							$r['from_image'] = $fromContact['image'];		
							
							if($fromContact['type']==1){	
								$r['app'] = array();
								$r['app']['name'] = $fromContact['name'];
								$r['app']['premium_flag'] = $fromContact['premium_flag'];
								$r['app']['header'] = $fromContact['header'];
								$r['app']['color_custom'] = $fromContact['color_custom'];
								$r['app']['color_custom_flag'] = $fromContact['color_custom_flag'];
								$r['app']['color_font'] = $fromContact['color_font'];
							}
							$r['to_name'] = $toContact['name'];
							$r['to_image'] = $toContact['image'];
							$message[] = $r;
						}						
					}            
					$stmt->close();															
				} 
							
				
				return $message;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function viewMessageHeaderUnread($token){		
		$message = array();
		$message['messages'] = $this->getMessageHeaderUnread($token);				
		return $message;
	}
	
	public function getMessageHeaderUnread($token){
		if(!empty($token)){
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){
				$message = array();
				
				// get message
				$stmt = $this->conn->prepare("SELECT if(msum.to_type=0 AND msum.to_id=1763,1,0) is_admin, if(msum.to_type=0,(select users.view_uid from users where users.id=msum.to_id),(select app.view_uid from app where app.id=msum.to_id)) to_id, msum.to_type, if(msum.from_type=0,(select users.view_uid from users where users.id=msum.from_id),(select app.view_uid from app where app.id=msum.from_id)) from_id, msum.from_type, msum.creation_date last_date, if((msum.role=1 AND msum.remove_from=0) or (msum.role=0 AND msum.remove_to=0),(case when msum.type=1 then 'Mengirim Foto untuk Anda' when msum.type=2 then 'Mengirim File untuk Anda' else msum.content end),null) last_content, msum.role, msum.unread FROM (SELECT m.content, mh.to_id, mh.to_type, mh.from_id, mh.from_type, m.creation_date, IF(mh.from_type=m.from_type AND mh.to_type=m.to_type,1,0) role, m.remove_from, m.remove_to, m.type, mh.unread FROM message_header mh, message m where mh.unread>0 AND mh.status=1 AND mh.from_user_id=? AND mh.last_message_id = m.id
				ORDER BY mh.last_message_id DESC) msum;");
 
				$stmt->bind_param("i", $user['id']);
				
				if ($stmt->execute()) {				
					$result = $stmt->get_result();
					while($r = $result->fetch_assoc()) {                                        						
						$fromContact = $this->getContact($r['from_type'], $r['from_id']);					
						$toContact = $this->getContact($r['to_type'], $r['to_id']);
						if($fromContact!=false && $toContact!=false){
							$r['from_name'] = $fromContact['name'];
							$r['from_image'] = $fromContact['image'];									
							if($fromContact['type']==1){	
								$r['app'] = array();
								$r['app']['name'] = $fromContact['name'];
								$r['app']['premium_flag'] = $fromContact['premium_flag'];
								$r['app']['header'] = $fromContact['header'];
								$r['app']['color_custom'] = $fromContact['color_custom'];
								$r['app']['color_custom_flag'] = $fromContact['color_custom_flag'];
								$r['app']['color_font'] = $fromContact['color_font'];
							}
							$r['to_name'] = $toContact['name'];
							$r['to_image'] = $toContact['image'];
							$message[] = $r;
						}						
					}            
					$stmt->close();															
				} 
							
				
				return $message;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function verifyEmailByToken($token){		
		if(!empty($token)){			
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){								
				return $this->verifyEmail($user);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	public function verifyEmailByUserId($user_id){		
		if(!empty($user_id)){			
			$stmt = $this->conn->prepare("SELECT users.id, view_uid, users.username, users.email, users.name, users.app_id,users.verified_email_token,users.verified_email_type FROM users WHERE users.status = 10 and id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();	
			if(!empty($user)){								
				return $this->verifyEmail($user);
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function verifyEmail($user){		
		if($user['app_id']!=-1){
			$stmt = $this->conn->prepare("SELECT view_uid, is_web, domain_name, domain_suffix, need_verified_email, verified_email_type FROM app WHERE app.status = 1 and id = ?");
			$stmt->bind_param("i", $user['app_id']);
			$stmt->execute();
			$app = $stmt->get_result()->fetch_assoc();
			$stmt->close();			
		} else {
			$app = array();
			$app['need_verified_email'] = 1;
			$app['verified_email_type'] = 0;
		}
		
		if($user['app_id']==-1 || $app['need_verified_email']==1){
			$userId = $user['id'];						
			
			if(isset($user['verified_email_type']) && isset($user['verified_email_token']) && $app['verified_email_type']==$user['verified_email_type']){
				$email_token = $user['verified_email_token'];
			} else {
				if($app!=null && $app['verified_email_type']==1){
					$email_token = $this->generateRandomNumber();
				} else {
					$email_token = $this->generateRandomString();
				}			
				
				$stmt = $this->conn->prepare("UPDATE users SET verified_email_token = ?, verified_email_type = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?");     
				$stmt->bind_param("siii", $email_token, $app['verified_email_type'], $userId, $userId);
				$result = $stmt->execute();
				$stmt->close();
			}
			
			
			if($user['app_id']!=-1){			
				if($app['is_web']==3){
					$domain = 'https://www.'.$app['domain_name'].'.'.$app['domain_suffix'];
				} else {
					$domain = 'https://www.jagel.id';
				}
				$url = $domain.'/verify-email?email='.$user['email'].'&token='.$email_token.'&appuid='.$app['view_uid'];
			} else {
				$domain = 'https://www.jagel.id';
				$url = $domain.'/verify-email?email='.$user['email'].'&token='.$email_token;
			}					
			
			// Email to user
			require_once "Email.php";
					
			$JJemail = new JJEmail(); 	
			if($app['verified_email_type']==1){
				$JJemail->verifyUsersEmailOTP($user['email'], $user['name'], $email_token, $user['app_id']);	
			} else {
				$JJemail->verifyUsersEmail($user['email'], $user['name'], $url, $user['app_id']);	
			}			
			
			$result = array();			
			$result["message"] = "E-mail telah terkirim, silahkan cek E-mail Anda dan segera lakukan verifikasi.";			
			$result["verified_email_type"] = $app['verified_email_type'];			
			return $result;
		} else {		
			return false;
		}		
	}

	public function generateRandomString($length = 40) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	public function generateRandomNumber($length = 5) {
		$characters = '0123456789';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	public function verifyEmailValidate($token, $verification_code, $appId){		
		if(!empty($token)){			
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){								
				$stmt = $this->conn->prepare("SELECT users.verified_email, users.verified_email_token, users.verified_email_type FROM users WHERE users.status = 10 and id = ?");
				$stmt->bind_param("i", $user['id']);
				$stmt->execute();
				$userVerified = $stmt->get_result()->fetch_assoc();
				$stmt->close();	
				
				if($userVerified['verified_email']==1 || $userVerified['verified_email_type']!=1){
					$response["error"] = TRUE;		
					$response["error_msg"] = "Terjadi kesalahan saat proses verifikasi!";
					echo json_encode($response);					
					exit;
				}
				
				if(!$this->eligibleValidate($user['id'])){
					$response["error"] = TRUE;		
					$response["error_msg"] = "Terlalu banyak kesalahan. Mohon tunggu beberapa saat lagi.";
							
					echo json_encode($response);
					exit;
				}
				
				if($userVerified['verified_email_token']==$verification_code){
					$stmt = $this->conn->prepare("UPDATE users SET verified_email=1, verified_time=NOW(), last_update_date=NOW(), last_updated_by=? WHERE id=? AND app_id=?");
					$stmt->bind_param("iii", $user['id'], $user['id'], $appId);
					$stmt->execute();					
					$stmt->close();	
										
					return true;
				} else {				
					$this->addUserBehavior("WRONG_VALIDATE_EMAIL", $user['id'], $appId);
				
					$response["error"] = TRUE;		
					$response["error_msg"] = "Kode verifikasi tidak sesuai.";
							
					echo json_encode($response);
					exit;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}	
	
	public function openOrder($token, $orderNo){
		if(!empty($token)){
			require_once '../include/DB_Auth.php';		
			$user = DB_Auth::getUser($token, $this->conn);
			if(!empty($user)){
			$stmt = $this->conn->prepare("SELECT customer_id, (select owner from app where app.id=supplier_id) owner_id, unique_id, order_status from order_header where status=1 and order_no=?");		
				$stmt->bind_param("s", $orderNo);
				if ($stmt->execute()) {            
					$order = $stmt->get_result()->fetch_assoc();								
					$stmt->close();            				
					$result = array();
					if($order['owner_id']==$user['id'] && $order['customer_id']==$user['id']){
						$result['status'] = 2;
					} else if($order['owner_id']==$user['id']) {
						$result['status'] = 1;
					} else if($order['customer_id']==$user['id']) {
						$result['status'] = 0;
					} else {
						$result['status'] = -1;
					}					
					$result['unique_id'] = $order['unique_id'];
					$result['order_status'] = $order['order_status'];
					return $result;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function getContact($type, $view_uid){	
		$result = -1;
		if($type==0){
			$stmt = $this->conn->prepare("SELECT id, name, photo image, id user_id, 0 type from users where status=10 and view_uid=?");		
			$stmt->bind_param("s", $view_uid);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();			
				$stmt->close();            				
				return $result;
			}
		} else if($type==1){
			$stmt = $this->conn->prepare("SELECT app.id, app.name, app.icon image, app.owner user_id, 1 type, (if(curdate()<=date(users.premium_date),1,0)) premium_flag, app.header, app.color_custom, app.color_custom_flag, app.color_font from app, users where app.status=1 and app.view_uid=? and app.owner= users.id");		
			$stmt->bind_param("s", $view_uid);
			if ($stmt->execute()) {            
				$result = $stmt->get_result()->fetch_assoc();			
				$stmt->close();            				
				return $result;
			}
		} else if($type==2){
			$result = array();
			$result['id'] = $this->user['id'];
			$result['name'] = $this->user['name'];
			$result['image'] = $this->user['photo'];
			$result['user_id'] = $this->user['id'];
		}
		return $result;
	}	
	
	/**
     * Storing new user
     * returns user details
     */
    public function storeUser($name, $email, $password, $fb_userId = null, $username = null, $appId = -1, $phone = null) {	
		ignore_user_abort(true);
		set_time_limit(1000);
		ini_set('max_execution_time', 1000);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;          
        }
		
        $uuid = uniqid(date('siHyz'), true);
        $view_uid = uniqid(date('siHzy'), true);
		$subscribeToken = uniqid(date('iszyH'), true);
        if(empty($password)){
            $password = uniqid(date('yzHis'), true);
            $updatedPassword = 0;
        } else {
            $updatedPassword = 1;
			if(strlen($password)<PASSWORDMINLENGTH){
				return false;
			}
        }
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
 
		$register_ip = $this->get_client_ip();
		if(empty($register_ip)){
			$register_ip = '-';
		}
 
        $stmt = $this->conn->prepare("INSERT INTO users(unique_id, view_uid, name, email, encrypted_password, salt, updated_password, creation_date, fb_id, subscribe_token, app_id, phone, register_ip) VALUES(?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssississ", $uuid, $view_uid, $name, $email, $encrypted_password, $salt, $updatedPassword, $fb_userId, $subscribeToken, $appId, $phone, $register_ip);
        $result = $stmt->execute();
        $stmt->close();
 
        // check for successful store
        if ($result) {	
			if(!empty($email)){
				$stmt = $this->conn->prepare("SELECT users.id FROM users WHERE (email = ?) and app_id = ?");
				$stmt->bind_param("si", $email, $appId);
			} else {
				$stmt = $this->conn->prepare("SELECT users.id FROM users WHERE (fb_id = ?) and app_id = ?");
				$stmt->bind_param("si", $fb_userId, $appId);
			}
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			
			$stmt = $this->conn->prepare("INSERT INTO users_address(user_id,creation_date,created_by,last_update_date,last_updated_by) VALUES(?,now(),?,now(),?)");
			$stmt->bind_param("iii", $user['id'], $user['id'], $user['id']);
			$result = $stmt->execute();
			$stmt->close();
			
			if(!empty($username)){
				$this->changeUsername($user['id'], $username, $appId);
			}
										
			if(isset($_POST['ref'])){
				$this->updateRef($user['id'], $_POST['ref'], $appId);
			}
				            
			if(!empty($email)){
				$stmt = $this->conn->prepare("SELECT users.id, view_uid, users.username, users.email, users.name, users.app_id, users.verified_email, users.verified_email_type, users.verified_email_token FROM users WHERE users.status = 10 and (email = ?) and app_id = ?");
				$stmt->bind_param("si", $email, $appId);
			} else {
				$stmt = $this->conn->prepare("SELECT users.id, view_uid, users.username, users.email, users.name, users.app_id, users.verified_email, users.verified_email_type, users.verified_email_token FROM users WHERE users.status = 10 and (fb_id = ?) and app_id = ?");
				$stmt->bind_param("si", $fb_userId, $appId);
			}
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();			
			
			if($email!=null){
				// Email to user
				include "Email.php";
				$JJemail = new JJEmail(); 
				if($appId==-1){	
					$JJemail->welcomeEmail($email, $name, $subscribeToken);
					$this->verifyEmail($user);											
				} else {
					$app = $this->getAppByAppId($appId);
					$this->verifyEmail($user);
					if($app['platinum_flag']==1 && $app['white_label_flag']==1){
						$JJemail->welcomeAppEmail($email, $name, $subscribeToken, $appId, $app, 1);										
					} else {
						$JJemail->welcomeAppEmail($email, $name, $subscribeToken, $appId, $app);										
					}
					
					$stmt = $this->conn->prepare("SELECT users.id, view_uid, users.username, users.email, users.name, users.app_id, users.verified_email, users.verified_email_type FROM users WHERE users.status = 10 and id=? and app_id = ?");
					$stmt->bind_param("ii", $user['id'], $appId);
					$stmt->execute();
					$user = $stmt->get_result()->fetch_assoc();
					$stmt->close();						
				}				
			}			
 
            return $user;
        } else {
            return false;
        }
    }
	
	// Function to get the client IP address
	function get_client_ip() {
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP']))
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_X_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if(isset($_SERVER['HTTP_FORWARDED']))
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if(isset($_SERVER['REMOTE_ADDR']))
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}
	
	public function addUserBehavior($action, $attr1, $attr2){	
		$ip_address = $this->get_client_ip();
		$stmt = $this->conn->prepare("INSERT INTO users_behavior_log(action, attr1, attr2, ip_address, creation_date) VALUES(?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $action, $attr1, $attr2, $ip_address);
        $result = $stmt->execute();
        $stmt->close();
	}
	
	public function eligibleLogin(){				
		$ip_address = $this->get_client_ip();
		$limit_login = LIMITLOGIN;
		$interval_login = INTERVALLOGIN;
		$stmt = $this->conn->prepare("select count(*) cnt from users_behavior_log where action='WRONG_PASSWORD' AND ip_address=? AND creation_date >= (now() - interval ? MINUTE) AND creation_date <= now()");
		$stmt->bind_param("si", $ip_address, $interval_login);
		$stmt->execute();
		$cnt = $stmt->get_result()->fetch_assoc();
		$stmt->close();		
		
		if($cnt['cnt']<=$limit_login){
			return true;
		} else {
			return false;
		}
	}
	
	public function eligibleValidate($userId){				
		$ip_address = $this->get_client_ip();
		$limit_validate = LIMITVALIDATE;
		$interval_validate = INTERVALVALIDATE;
		$stmt = $this->conn->prepare("select count(*) cnt from users_behavior_log where action='WRONG_VALIDATE_EMAIL' AND ip_address=? AND creation_date >= (now() - interval ? MINUTE) AND creation_date <= now() AND attr1=?");
		$stmt->bind_param("sii", $ip_address, $interval_validate, $userId);
		$stmt->execute();
		$cnt = $stmt->get_result()->fetch_assoc();
		$stmt->close();		
		
		if($cnt['cnt']<=$limit_validate){
			return true;
		} else {
			return false;
		}
	}
	
	public function eligibleReset(){				
		$ip_address = $this->get_client_ip();
		$limit_login = LIMITLOGIN;
		$interval_login = INTERVALLOGIN;
		$stmt = $this->conn->prepare("select count(*) cnt from users_behavior_log where action='WRONG_RESET_TOKEN' AND ip_address=? AND creation_date >= (now() - interval ? MINUTE) AND creation_date <= now()");
		$stmt->bind_param("si", $ip_address, $interval_login);
		$stmt->execute();
		$cnt = $stmt->get_result()->fetch_assoc();
		$stmt->close();		
		
		if($cnt['cnt']<=$limit_login){
			return true;
		} else {
			return false;
		}
	}
	
	public function triggerNewUser($appId, $user, $token){
		if($appId!=-1){	
			// get app data for welcome message and notification new user
			$stmt = $this->conn->prepare("SELECT app.id, app.view_uid, app.name, app.welcome_flag, app.welcome_message, app.notif_new_user, app.owner, (if(curdate()<=date(users.premium_date),1,0)) premium_flag, users.view_uid owner_view_uid, app.block_user_first FROM app, users WHERE app.status = 1 and app.id = ? and users.id = app.owner and users.status=10");
			$stmt->bind_param("i", $appId);
			$stmt->execute();
			$app = $stmt->get_result()->fetch_assoc();
			$stmt->close();	
			if($app!=null){
				if($app['notif_new_user']==1){
					if($app['premium_flag']==1){
						$msg = array('message' => "Ada pengguna baru! Username: ".$user['username'], 'view_uid' => $user['view_uid'], 'app_view_uid' => $app['view_uid'], 'category' => '8', 'title' => $app['name']);
					} else {
						$msg = array('message' => "Ada pengguna baru!", 'view_uid' => '', 'category' => '8', 'title' => $app['name']);
					}
					$tokens = Functions::getTokens($this->conn, $app['owner'], -1);
					if (!empty($tokens)) {     								
						foreach ($tokens as $_token) {				
							Functions::sendNotification($_token, $msg);		
						}				
					}								
				}
				if($app['premium_flag']==1 && $app['welcome_flag']==1){
					if(strlen($app['welcome_message'])==0){
						$app['welcome_message'] = ' ';
					}									

					require_once '../message/DB_Functions_Message.php';		
					$dbMessage = new DB_Functions_Message($token); 
					
					$dbMessage->sendCustom(1, $app['view_uid'], 0, $user['view_uid'], $app['welcome_message'], $app['id']);
				}
				if($app['premium_flag']==1 && $app['block_user_first']==1){
					// change users status to blocked
					$stmt = $this->conn->prepare("UPDATE users SET status=-1, last_update_date=NOW(), last_updated_by=? WHERE id=? AND app_id=?");
					$stmt->bind_param("iii", $user['id'], $user['id'], $appId);
					$stmt->execute();					
					$stmt->close();	
					
					$response["error"] = TRUE;
					$response["error_msg"] = "Pendaftaran diterima, silahkan hubungi Admin untuk mengaktifkan akun Anda.";
					
					echo json_encode($response);
					exit;
				}
			}
		}
	}
	
	public function checkEligibleUsername($username, $appId = -1) {	
	
		if(strlen($username)>USERNAMELENGTH){
            $response["error"] = TRUE;
			$response["error_msg"] = "Username length can't be greater than ". USERNAMELENGTH . " characters";
			
			echo json_encode($response);
			exit;
        }
		
		if(strlen($username)<4){
            $response["error"] = TRUE;
			$response["error_msg"] = "Username harus lebih atau sama dengan 4 karakter";
			
			echo json_encode($response);
			exit;
        }
 
		$stmt = $this->conn->prepare("SELECT count(*) cnt FROM users_username WHERE status=1 AND username=? AND app_id=?"); 
		$stmt->bind_param("si", $username, $appId);
 
		if ($stmt->execute()) {
			$count = $stmt->get_result()->fetch_assoc();
			$stmt->close();
			if($count['cnt']==0){
				return true;
			} else {				
				$response["error"] = TRUE;
				$response["error_msg"] = "Username have been picked by someone else";
				
				echo json_encode($response);
				exit;
			}
		} else {
			return "Error2";
		}			
    }
	
	public function changeUsername($userId, $username, $appId = -1) {	
	
		if(strlen($username)>USERNAMELENGTH){
            return "Username length can't be greater than ". USERNAMELENGTH . " characters";
        }
		
		if(strlen($username)<4){
            return "Username harus lebih atau sama dengan 4 karakter";
        }
	
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
							       
							return "Username changed";
						} else {
							return "Error1";
						}
					} else {
						return "Username have been picked by someone else";
					}
				} else {
					return "Error2";
				}
			} else {
				return "You have changed your usename a while ago";
			}            
        } else {
            return "Error3";
        }
    }
	
	public function isUserExisted($email, $appId = -1) {
        $stmt = $this->conn->prepare("SELECT email from users WHERE email = ? and app_id = ?");
 
        $stmt->bind_param("si", $email, $appId);
 
        $stmt->execute();
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // user existed 
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }
	
	public function isUsernameExisted($username, $appId = -1) {
        $stmt = $this->conn->prepare("SELECT username FROM users_username WHERE status=1 AND username=? AND app_id = ?");
 
        $stmt->bind_param("si", $username, $appId);
 
        $stmt->execute();
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // user existed 
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }
	
	public function isPhoneExisted($phone, $appId = -1) {
        $stmt = $this->conn->prepare("SELECT phone FROM users WHERE phone=? AND app_id = ?");
 
        $stmt->bind_param("si", $phone, $appId);
 
        $stmt->execute();
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // user existed 
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }
	
	public function isUserExistedFbId($fb_userId, $appId = -1) {
        $stmt = $this->conn->prepare("SELECT fb_id from users WHERE fb_id = ? and app_id = ?");
 
        $stmt->bind_param("si", $fb_userId, $appId);
 
        $stmt->execute();
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // user existed 
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }
	
	public function getUserByUsernameAndPassword($username, $password, $appId = -1) {
 
		if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            $stmt = $this->conn->prepare("SELECT id, salt, encrypted_password, verified_email_type, verified_email FROM users WHERE ((length(username)>0 and username = ?) or (length(phone)>0 and phone=?)) AND status = 10 and app_id = ?");       
			$stmt->bind_param("ssi", $username, $username, $appId);
        } else {
			$stmt = $this->conn->prepare("SELECT id, salt, encrypted_password, verified_email_type, verified_email FROM users WHERE email
			= ? AND status = 10 and app_id = ?");
			$stmt->bind_param("si", $username, $appId);
		}               
 
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
             
            $salt = $user['salt'];
            $encrypted_password = $user['encrypted_password'];
            $hash = $this->checkhashSSHA($salt, $password);
            
            if ($encrypted_password == $hash) {
                return $user;
            } else {
				$this->addUserBehavior("WRONG_PASSWORD", $username, $appId);
				return false;
			}
        } else {
			$this->addUserBehavior("WRONG_USER", $username, $appId);
            return false;
        }
    }
	
	public function getUserByFacebookToken($userId, $token, $gender, $appId = -1) {
 
		$ch = curl_init(); 
        // set url 
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/me?access_token=" . $token . "&fields=name,email"); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        
        $output = curl_exec($ch); 		
        
        curl_close($ch);  		
		$array = json_decode($output, true);
		
		if(isset($array["error"])){			
			return NULL;
		}
		
		$fb_name = $array['name'];
		$fb_userId = $array['id'];		
		if(isset($array["email"])){
			$fb_email = $array['email'];				
		} else {
			$fb_email = null;
		}		
				
		if($fb_userId != $userId){			
			return NULL;
		}
		
        $stmt = $this->conn->prepare("SELECT users.id, view_uid FROM users WHERE users.status = 10 and fb_id = ? and app_id = ?");
 
        $stmt->bind_param("ii", $fb_userId, $appId);
 
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			            
            if(isset($user)){		
				// if fb user is exist, then login				
				return $user;
			} else {				
				// if fb user not exist, then create user
				if($fb_email == null || (!$this->isUserExisted($fb_email, $appId) && !$this->isUserExistedFbId($fb_userId, $appId))){
					$user = $this->storeUser($fb_name, $fb_email, "", $fb_userId, "", $appId);

                    $imageURL = "https://graph.facebook.com/" . $fb_userId . "/picture?type=large";                    
                    $imageName = str_replace(' ', '', $fb_name) . "~" . $user['view_uid'] . ".jpg";
                    // update photo user
                    copy($imageURL, ROOT . "userphoto/" . $imageName);
                    $this->updateUserPhoto($user['id'],$imageName);					
				} else if($fb_email!=null){
					$stmt = $this->conn->prepare("SELECT users.id FROM users WHERE users.status = 10 and email = ? and app_id = ?");
					$stmt->bind_param("si", $fb_email, $appId);
					$stmt->execute();
					$user = $stmt->get_result()->fetch_assoc();				
					$stmt->close();					
				} else {
					$stmt = $this->conn->prepare("SELECT users.id FROM users WHERE users.status = 10 and fb_id = ? and app_id = ?");
					$stmt->bind_param("si", $fb_userId, $appId);
					$stmt->execute();
					$user = $stmt->get_result()->fetch_assoc();				
					$stmt->close();					
				}				
			
				$this->updateUserFb($user["id"], $fb_userId, $gender);
				return $user;
			}            
        } else {		
            return NULL;
        }
    }
	
	public function getUserByGoogleToken($userId, $token, $appId = -1) {

		// create curl resource 
        $ch = curl_init(); 
        // set url 
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=" . $token); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //return the transfer as a string 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        // $output contains the output string 
        $output = curl_exec($ch); 		
        // close curl resource to free up system resources 
        curl_close($ch);  		
		$array = json_decode($output, true);
		
		if(isset($array["error_description"])){
			// There is error when validating using Google
			return NULL;
		}
		
		$google_name = $array['name'];
		$google_userId = $array['sub'];		
		$google_email = $array['email'];				
		
		if($google_userId != $userId){
			// User id and token requested is not the same
			return NULL;
		}
		
        $stmt = $this->conn->prepare("SELECT users.id, view_uid FROM users WHERE users.status = 10 and google_id = ? and app_id = ?");
 
        $stmt->bind_param("ii", $google_userId, $appId);
 
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			            
            if(isset($user)){						
				// if google user is exist, then login				
				return $user;
			} else {							
				// if google user not exist, then create user
				if(!$this->isUserExisted($google_email, $appId)){				
					$user = $this->storeUser($google_name, $google_email, "", "", "", $appId);
                    $imageURL = $array['picture'] . "?sz=250";                    
                    $imageName = str_replace(' ', '', $google_name) . "~" . $user['view_uid'] . ".jpg";
                    // update photo user
                    copy($imageURL, ROOT . "userphoto/" . $imageName);
                    $this->updateUserPhoto($user['id'],$imageName);
				} else {
					$stmt = $this->conn->prepare("SELECT users.id FROM users WHERE users.status = 10 and email = ? and app_id = ?");
					$stmt->bind_param("si", $google_email, $appId);
					$stmt->execute();
					$user = $stmt->get_result()->fetch_assoc();				
					$stmt->close();					
				}
				
				$this->updateUserGoogle($user["id"], $google_userId);
				return $user;
			}            
        } else {		
            return NULL;
        }
    }
	
	public function getUserByEmail($email, $appId = -1) {
		
        $stmt = $this->conn->prepare("SELECT users.id, view_uid, password_reset_token, email, name, app_id, (select app.name from app where app.id=app_id) app_name FROM users WHERE users.status = 10 and email = ? and app_id = ?");
 
        $stmt->bind_param("si", $email, $appId);
 
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			            
            if(isset($user)){										
				return $user;
			} else {											
				return false;
			}            
        } else {		
            return false;
        }
    }
	
	public function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = 3600;//Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }
	
	public function generatePasswordResetToken($userId, $app = null)
    {
		if($app!=null && $app['password_reset_type']==1){
			$password_reset_token = $this->generateRandomNumber() . $this->generateRandomString() . '_' . time();
		} else {
			$password_reset_token = $this->generateRandomString() . '_' . time();
		}
		
		$stmt = $this->conn->prepare("UPDATE users SET last_update_date = NOW(), last_updated_by = ?, password_reset_token = ? WHERE id = ?");		
        $stmt->bind_param("isi", $userId, $password_reset_token, $userId);
        $result = $stmt->execute();
        $stmt->close();
		
		$stmt = $this->conn->prepare("SELECT users.id, view_uid, password_reset_token, email, name, app_id, (select app.name from app where app.id=app_id) app_name FROM users WHERE users.status = 10 and id = ?");
        $stmt->bind_param("i", $userId);
 
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			            
            if(isset($user)){										
				return $user;
			} else {											
				return false;
			}            
        } else {		
            return false;
        }
    }
	
	public function updateUserFb($id, $fb_id, $fb_gender) {        	
        $stmt = $this->conn->prepare("UPDATE users SET last_update_date = NOW(), last_updated_by = ?, fb_id = ?, gender = ? WHERE id = ?");		
        $stmt->bind_param("issi", $id, $fb_id, $fb_gender, $id);
        $result = $stmt->execute();
        $stmt->close();
    }	
	
    public function updateUserGoogle($id, $google_userId) {        
        $stmt = $this->conn->prepare("UPDATE users SET last_update_date = NOW(), last_updated_by = ?, google_id = ? WHERE id = ?");				
        $stmt->bind_param("isi", $id, $google_userId, $id);
        $result = $stmt->execute();
        $stmt->close();
    }

    public function updateUserPhoto($userId, $filename) {        
        $stmt = $this->conn->prepare("UPDATE users SET photo = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?");     
        $stmt->bind_param("sii", $filename, $userId, $userId);
        $result = $stmt->execute();
        $stmt->close();
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
	
	public function updateUserToken($id, $token, $appId, $hl = null, $firebase_type = 0, $imei = null) {        
		if($appId!=-1){
			$stmt = $this->conn->prepare("SELECT ase.login_type from app left join app_setting ase on ase.app_id=app.id WHERE app.id = ?");
			$stmt->bind_param("i", $appId);
			
			// login type = 1 > login with one device
			if ($stmt->execute()) {
				$app = $stmt->get_result()->fetch_assoc();                                
				$stmt->close();			
				if($app['login_type']==1){
					$stmt = $this->conn->prepare("UPDATE users_token SET last_update_date = NOW(), last_updated_by = ?, status=0 WHERE user_id = ? and status=1");     
					$stmt->bind_param("ii", $id, $id);
					$result = $stmt->execute();
					$stmt->close();
				}
			}
		}        
	
        $stmt = $this->conn->prepare("UPDATE users SET last_update_date = NOW(), last_updated_by = ?, token = ?, language = ?, imei = ? WHERE id = ?");     
        $stmt->bind_param("isssi", $id, $token, $hl, $imei, $id);
        $result = $stmt->execute();
        $stmt->close();

        // generate access token
        $accessToken = $this->generateAccessToken();

		$ip_address = $this->get_client_ip();
		
        // insert into users_token        
        $stmt = $this->conn->prepare("INSERT INTO users_token(user_id, app_id, token, access_token, language, creation_date, created_by, last_update_date, last_updated_by, status, firebase_type, imei, ip_address) values (?, ?, ?, ?, ?, NOW(), ?, NOW(), ?, 1, ?, ?, ?)");     
        $stmt->bind_param("iisssiiiss", $id, $appId, $token, $accessToken, $hl, $id, $id, $firebase_type, $imei, $ip_address);
        $result = $stmt->execute();
        $stmt->close();

        return $accessToken;
    }
	
	public function refreshUserToken($id, $access_token, $token, $appId, $hl = null, $firebase_type = 0, $imei = null) {        		
	
        $ip_address = $this->get_client_ip();
		
        // insert into users_token        
        $stmt = $this->conn->prepare("UPDATE users_token SET token=?,firebase_type=?,last_update_date=now(),last_updated_by=? WHERE access_token=?");     
        $stmt->bind_param("siis", $token, $firebase_type, $id, $access_token);
        $result = $stmt->execute();
        $stmt->close();

        return true;
    }
	
	public function updateRef($id, $ref, $appId) {   		
		$stmt = $this->conn->prepare("SELECT referral_id from users WHERE users.status = 10 and users.id = ? and app_id = ?");
        $stmt->bind_param("ii", $id, $appId);
 
        if ($stmt->execute()) {
            $users = $stmt->get_result()->fetch_assoc();                                
            $stmt->close();			
            if($users['referral_id']==null){
				$stmt = $this->conn->prepare("SELECT id from users WHERE users.status = 10 and users.username = ? and app_id = ?");
				$stmt->bind_param("si", $ref, $appId);				
				if ($stmt->execute()) {
					$refUsers = $stmt->get_result()->fetch_assoc();                                
					$stmt->close();		 
					if($refUsers['id']!=null && $id!=$refUsers['id']){
						$stmt = $this->conn->prepare("UPDATE users SET last_update_date = NOW(), last_updated_by = ?, referral_id = ? WHERE id = ?");     
						$stmt->bind_param("iii", $id, $refUsers['id'], $id);
						$result = $stmt->execute();
						$stmt->close();
					}
				}
			}
        } 
    }
	
	function generateAccessToken(){
        do {
            $accessToken = $this->uniqueCharacter();            
        } while ($this->checkExists($accessToken));
        return $accessToken;
    }

    function uniqueCharacter($l = 50) {
        return substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }
	
	function checkExists($accessToken){
        $stmt = $this->conn->prepare("SELECT access_token from users_token WHERE access_token = ?");
        $stmt->bind_param("s", $accessToken);
        $stmt->execute();
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {            
            $stmt->close();
            return true;
        } else {            
            $stmt->close();
            return false;
        }
    }
	
	public function getUserByResetToken($reset_token, $appId, $type=0, $email=null) {
		
		if($this->eligibleReset()){
			if($type==1){
				$stmt = $this->conn->prepare("SELECT users.id from users WHERE users.status = 10 and app_id = ? and substring(password_reset_token,1,5)=? and email=?");
				$stmt->bind_param("iss", $appId, $reset_token, $email);
			} else {
				$stmt = $this->conn->prepare("SELECT users.id from users WHERE users.status = 10 and app_id = ? and password_reset_token =?");
				$stmt->bind_param("is", $appId, $reset_token);
			}				 		
	 
			if ($stmt->execute()) {
				$users = $stmt->get_result()->fetch_assoc();                                
				$stmt->close();
	 
				return $users;
			} else {
				$this->addUserBehavior("WRONG_RESET_TOKEN", $reset_token, $appId);
				return false;
			}
		} else {
			$response["error"] = TRUE;
			$response["error_msg"] = "Mohon coba beberapa saat lagi";
			
			echo json_encode($response);
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
	
	public function getAppByViewUId($vuid) {
 
        $stmt = $this->conn->prepare("SELECT app.id, app.need_imei from app WHERE app.status = 1 and app.view_uid = ?");
 
        $stmt->bind_param("s", $vuid);
 
        if ($stmt->execute()) {
            $app = $stmt->get_result()->fetch_assoc();                                
            $stmt->close();
			
			$this->checkEligibleApp($app);
 
            return $app;
        } else {
            return false;
        }
    }
	
	public function checkEligibleApp($app){
		if($app['need_imei']==1 && (!isset($_POST['imei']) || empty($_POST['imei']))){
			$response["error"] = TRUE;
			$response["error_msg"] = "Anda perlu mengijinkan akses untuk perangkat";		 
			
			echo json_encode($response);
			exit;
		}
		if(isset($_POST['imei']) && !empty($_POST['imei'])){
			$imei = $_POST['imei'];
			/*if($app['need_imei']==1 && $imei=='-----'){
				$response["error"] = TRUE;
				$response["error_msg"] = "Ijin untuk perangkat tidak ditemukan";		 
				
				echo json_encode($response);
				exit;
			}*/
			$stmt = $this->conn->prepare("SELECT count(*) cnt from users_imei_block uib WHERE uib.status = 1 and uib.app_id = ? and uib.imei=?"); 
			$stmt->bind_param("is", $app['id'], $imei);
	 
			if ($stmt->execute()) {
				$imeiBlock = $stmt->get_result()->fetch_assoc()['cnt'];
				$stmt->close();
				
				if($imeiBlock>0){
					$this->addUserBehavior("IMEI_BLOCK", $app['id'], $imei);
					$response["error"] = TRUE;
					$response["error_msg"] = "Anda tidak bisa masuk ke aplikasi dengan perangkat ini";		 
					
					echo json_encode($response);
					exit;
				}
			}
		}
		if(isset($_POST['phone']) && !empty($_POST['phone'])){
			
			$phone = $_POST['phone'];			
			$stmt = $this->conn->prepare("SELECT count(*) cnt from users_phone_block uib WHERE uib.status = 1 and uib.app_id in (?,-2) and uib.phone=?"); 
			$stmt->bind_param("is", $app['id'], $phone);
	 
			if ($stmt->execute()) {
				$phoneBlock = $stmt->get_result()->fetch_assoc()['cnt'];
				$stmt->close();
				
				if($phoneBlock>0){
					$response["error"] = TRUE;
					$response["error_msg"] = "Anda tidak bisa masuk ke aplikasi dengan perangkat ini";		 
					
					echo json_encode($response);
					exit;
				}	 			
			} 
		}
		
		if(isset($_POST['email']) && !empty($_POST['email'])){			
			$email = $_POST['email'];			
			$stmt = $this->conn->prepare("SELECT count(*) cnt from users_email_block uib WHERE uib.status = 1 and uib.app_id in (?,-2) and uib.email=?"); 
			$stmt->bind_param("is", $app['id'], $email);
	 
			if ($stmt->execute()) {
				$emailBlock = $stmt->get_result()->fetch_assoc()['cnt'];
				$stmt->close();
				if($emailBlock>0){
					$response["error"] = TRUE;
					$response["error_msg"] = "Anda tidak bisa masuk ke aplikasi dengan perangkat ini";		 
					
					echo json_encode($response);
					exit;
				}	 			
			}
		}
		return true;
	}
	
	public function checkEligibleImei(){
		if(isset($_POST['imei']) && !empty($_POST['imei'])){
			$appId = -1;
			$imei = $_POST['imei'];
			$stmt = $this->conn->prepare("SELECT count(*) cnt from users_imei_block uib WHERE uib.status = 1 and uib.app_id in (?,-2) and uib.imei=?"); 
			$stmt->bind_param("is", $appId, $imei);
	 
			if ($stmt->execute()) {
				$imeiBlock = $stmt->get_result()->fetch_assoc()['cnt'];
				$stmt->close();
				
				if($imeiBlock>0){
					$this->addUserBehavior("IMEI_BLOCK", $appId, $imei);
					$response["error"] = TRUE;
					$response["error_msg"] = "Anda tidak bisa masuk ke aplikasi dengan perangkat ini";		 
					
					echo json_encode($response);
					exit;
				}
	 
				return true;
			} else {
				return false;
			}
		}
	}
	
	public function getAppByAppId($appId) {
 
        $stmt = $this->conn->prepare("SELECT app.id, app.name, app.email, app.phone, app.short_description, app.description, app.icon, app.white_label_flag, (if(users.platinum_date=null,0,if(curdate()<=date(users.platinum_date),1,0))) platinum_flag, app.share_path_host, app.share_path_scheme, ase.password_reset_type from app left join app_setting ase on ase.status=1 and ase.app_id=app.id, users WHERE app.status = 1 and app.id = ? AND users.id = app.owner AND users.status=10");
 
        $stmt->bind_param("i", $appId);
 
        if ($stmt->execute()) {
            $app = $stmt->get_result()->fetch_assoc();                                
            $stmt->close();
 
            return $app;
        } else {
            return false;
        }
    }
	
	public function resetPassword($user, $password){
		$hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt		
 
        $stmt = $this->conn->prepare("UPDATE users SET encrypted_password=?, salt=?, updated_password=1, password_reset_token=null, last_update_date=now(), last_updated_by=? WHERE id=?");
        $stmt->bind_param("ssii", $encrypted_password, $salt, $user['id'], $user['id']);
        $result = $stmt->execute();
        $stmt->close();
	}
	
	public function logout($token, $firebaseToken, $appid) {        
		require_once '../include/DB_Auth.php';		
		$user = DB_Auth::getUser($token, $this->conn);
		
        $stmt = $this->conn->prepare("UPDATE users_token SET last_update_date = NOW(), last_updated_by = ?, status = 0 WHERE access_token = ? AND status=1 AND user_id=? AND app_id=? AND token = ? AND app_id = ?");
        $stmt->bind_param("isiisi", $user['id'], $token, $user['id'], $appid, $firebaseToken, $appid);
        $result = $stmt->execute();
        $stmt->close();

        return true;
    }
	
	public function logoutAll($token, $firebaseToken, $appid) {        
		require_once '../include/DB_Auth.php';		
		$user = DB_Auth::getUser($token, $this->conn);
		
        $stmt = $this->conn->prepare("UPDATE users_token SET last_update_date = NOW(), last_updated_by = ?, status = 0 WHERE status=1 AND user_id=? AND app_id=?");
        $stmt->bind_param("iii", $user['id'], $user['id'], $appid);
        $result = $stmt->execute();
        $stmt->close();

        return true;
    }
	
	public function sendSubscriber($email_id){
		ignore_user_abort(true);
		set_time_limit(1000);
		ini_set('max_execution_time', 1000);
		
		$stmt = $this->conn->prepare("SELECT title, content from subscribe_email where id=?");		
		$stmt->bind_param("i", $email_id);
        if ($stmt->execute()) {            
            $sendEmail = $stmt->get_result()->fetch_assoc();            
            $stmt->close();            			
        }
				
		// Email to user
		include "Email.php";
		
		$JJemail = new JJEmail(); 		
				
		$stmt = $this->conn->prepare("SELECT email, name, subscribe_token from users where subscribe=1 and status=10 and id>237 and id<=250 order by id");		
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {                                        
                $JJemail->sendSubscribeEmail($r['email'], $r['name'], $r['subscribe_token'], $sendEmail['title'], $sendEmail['content']);
            }
            $stmt->close();            
			return true;
        } else {
			return false;
		}
	}
	
	public function sendWarningApp(){
		ignore_user_abort(true);
		set_time_limit(1000);
		ini_set('max_execution_time', 1000);				
				
		// Email to user
		include "Email.php";
		
		$JJemail = new JJEmail(); 		
				
		$stmt = $this->conn->prepare("SELECT random_token, users.name, users.email, app.name app_name, package_name from app,users where users.id = app.owner and app.status=1 and warning_flag=-2;");		
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {                                        
                $JJemail->sendWarningAppEmail($r['random_token'], $r['name'], $r['email'], $r['app_name'], $r['package_name']);
            }
            $stmt->close();            
			return true;
        } else {
			return false;
		}
	}
	
	public function locationUpdate($token, $lat, $lng){
		require_once '../include/DB_Auth.php';		
		$user = DB_Auth::getUser($token, $this->conn);
		if(!empty($user)){
			$userId = $user['id'];
			$stmt = $this->conn->prepare("UPDATE users SET driver_lat = ?, driver_lng = ?, last_update_date = NOW(), last_updated_by = ? WHERE id = ?");     
			$stmt->bind_param("ssii", $lat, $lng, $userId, $userId);
			$result = $stmt->execute();
			$stmt->close();
		}
	}
	
	public function getOrderLocation($token, $order_unique_id){
		require_once '../include/DB_Auth.php';		
		$user = DB_Auth::getUser($token, $this->conn);
		if(!empty($user)){
			$userId = $user['id'];
			
			$stmt = $this->conn->prepare("SELECT oh.courrier_type, driver.driver_lat driver_lat, driver.driver_lng driver_lng, IFNULL(customer.driver_lat, ol.customer_lat) customer_lat, IFNULL(customer.driver_lng, ol.customer_lng) customer_lng, if(oh.driver_assigned is not null, 1, 0) driver_assigned_flag, (select aci.icon from app_courrier_icon aci where aci.status=1 and aci.app_id=app.id and aci.courrier_type=oh.courrier_type AND oh.driver_flag=1 limit 1) driver_icon, (select aci.operator from app_courrier_icon aci where aci.status=1 and aci.app_id=app.id and aci.courrier_type=oh.courrier_type AND oh.driver_flag=1 limit 1) driver_operator
			FROM order_header oh
			LEFT JOIN users customer ON customer.id=oh.customer_id
			LEFT JOIN app ON app.id=oh.supplier_id AND app.status=1
			LEFT JOIN users supplier ON supplier.id=app.owner
			LEFT JOIN users driver ON driver.id=oh.driver_assigned
			LEFT JOIN order_line ol ON ol.header_id=oh.id AND ol.category=2
			WHERE oh.unique_id=? AND oh.status=1 AND oh.order_status=0 AND oh.driver_flag=1 AND (driver.id = ? OR customer.id=? OR supplier.id=? OR (oh.driver_assigned is null AND ? in (select driver2.id from users driver2 where driver2.app_id = oh.supplier_id and driver2.driver_status=2 and oh.courrier_type=driver2.driver_type)) OR app.id in (select admin.app_id from users admin where (admin.admin_status=1 OR admin.admin_status=2) AND admin.status=10 AND admin.id=?))"); 
			$stmt->bind_param("siiiii", $order_unique_id, $userId, $userId, $userId, $userId, $userId);
			if ($stmt->execute()) {
				$order = $stmt->get_result()->fetch_assoc();
				$stmt->close();
	 
				return $order;
			} else {
				return false;
			}
		}
	}
	
	public function sendNotification(){
		ignore_user_abort(true);
		set_time_limit(1000);
		ini_set('max_execution_time', 1000);			
				
		// Send notification to user
		include "Functions.php";			
							
		$title = 'Tes kirim pesan';
		$msg = 'Kirim pesan dulu ya';
		$message = array('category' => '99', 'message' => $msg, 'title' => $title);
		
		$stmt = $this->conn->prepare("SELECT id user_id from users where id=33");				
        if ($stmt->execute()) {            
            $result = $stmt->get_result();
            while($r = $result->fetch_assoc()) {                                        				
				$tokens = Functions::getTokens($this->conn, $r['user_id'], -1);
				foreach ($tokens as $token) {
					Functions::sendNotification($token, $message);		
				}		
            }
            $stmt->close();            
			return true;
        } else {
			return false;
		}
	}

}
 
?>
