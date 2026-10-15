<?php
if (get_option('siteurl') !== 'http://localhost:8099') { exit(1); }
function qa_assert($ok, $msg) { if (!$ok) {fwrite(STDERR,"FAIL ".$msg."\n");exit(1);}echo "PASS ".$msg."\n"; }
function qa_request($method,$path,$params=[]) {
 $req=new WP_REST_Request($method,'/advertpreneur-connect/v1/'.$path);
 foreach($params as $k=>$v)$req->set_param($k,$v);
 $response=rest_do_request($req);
 return [$response->get_status(),$response->get_data()];
}
wp_set_current_user(1);
$fixtures=get_posts(['post_type'=>'page','post_status'=>['publish','draft'],'posts_per_page'=>30,'orderby'=>'date','order'=>'DESC']);
$post_id=0;
foreach ($fixtures as $fixture) { if (strpos($fixture->post_title,'Advertpreneur Agensi QA Fixture ')===0) { $post_id=(int)$fixture->ID;break; } }
qa_assert($post_id>0,'isolated QA article fixture located dynamically');
$img=file_get_contents('/tmp/adpc-image.png');
qa_assert(strlen($img)>1000,'real test fixture bytes available');
[$status,$data]=qa_request('POST','media/base64',[
  'data'=>base64_encode($img),'filename'=>'adpc-qa-image.png','alt_text'=>'Multicolor gradient with rectangular highlight',
  'usage'=>'article_inline','strict_fit'=>false,'allow_crop'=>true,
]);
if ($status>=300) echo 'MEDIA_UPLOAD_ERROR '.json_encode(['status'=>$status,'code'=>$data['code']??'','message'=>$data['message']??''])."\n";
qa_assert($status<300 && !empty($data['media_id']),'real WordPress media image uploaded');
$id=(int)$data['media_id'];
qa_assert(wp_attachment_is_image($id),'attachment is a valid image');
$size=wp_get_attachment_metadata($id);
qa_assert(!empty($size['width']) && (int)$size['width']===1600,'image native size preserved');
$author=wp_insert_user(['user_login'=>'qa_media_author_'.time(),'user_email'=>'media-author'.time().'@localhost.invalid','user_pass'=>'TestOnlyNotProd','role'=>'contributor']);
qa_assert(!is_wp_error($author),'restricted media user created');
wp_set_current_user($author);
[$denied,$res]=qa_request('POST','posts/'.$post_id.'/featured',['media_id'=>$id,'allow_crop'=>true]);
qa_assert($denied===403,'unauthorized contributor cannot replace featured image');
wp_set_current_user(1);
[$status,$result]=qa_request('POST','posts/'.$post_id.'/featured',['media_id'=>$id,'allow_crop'=>true,'strict_fit'=>false]);
qa_assert($status<300 && ($result['featured_media']??0)===$id,'authorized featured image saved');
wp_delete_attachment($id,true);
wp_delete_post($post_id,true);
echo 'MEDIA_QA_PASSED (test page and image cleaned up)'."\n";
