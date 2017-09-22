<?php
/**
 * 游戏业务相关配置
 */
return array(
		'serverId'=>101,
		'aBet'=>array(
				1=>array('id'=>1,'type'=>1,'val'=>1,'odd'=>1,'th_desc'=>'小','zh_desc'=>'小','en_desc'=>'Small'),
				2=>array('id'=>2,'type'=>1,'val'=>2,'odd'=>1,'th_desc'=>'大','zh_desc'=>'大','en_desc'=>'Big'),
				
				3=>array('id'=>3,'type'=>3,'val'=>'','odd'=>80,'th_desc'=>'豹子','zh_desc'=>'豹子','en_desc'=>'豹子'),
				
				4=>array('id'=>4,'type'=>2,'val'=>4,'odd'=>50,'th_desc'=>'4點','zh_desc'=>'4点','en_desc'=>'4 pts'),
				5=>array('id'=>5,'type'=>2,'val'=>5,'odd'=>18,'th_desc'=>'5點','zh_desc'=>'5点','en_desc'=>'5 pts'),
				6=>array('id'=>6,'type'=>2,'val'=>6,'odd'=>14,'th_desc'=>'6點','zh_desc'=>'6点','en_desc'=>'6 pts'),
				7=>array('id'=>7,'type'=>2,'val'=>7,'odd'=>12,'th_desc'=>'7點','zh_desc'=>'7点','en_desc'=>'7 pts'),
				8=>array('id'=>8,'type'=>2,'val'=>8,'odd'=>8,'th_desc'=>'8點','zh_desc'=>'8点','en_desc'=>'8 pts'),
				9=>array('id'=>9,'type'=>2,'val'=>9,'odd'=>6,'th_desc'=>'9點','zh_desc'=>'9点','en_desc'=>'9 pts'),
				10=>array('id'=>10,'type'=>2,'val'=>10,'odd'=>6,'th_desc'=>'10點','zh_desc'=>'10点','en_desc'=>'10 pts'),
				11=>array('id'=>11,'type'=>2,'val'=>11,'odd'=>6,'th_desc'=>'11點','zh_desc'=>'11点','en_desc'=>'11 pts'),
				12=>array('id'=>12,'type'=>2,'val'=>12,'odd'=>6,'th_desc'=>'12點','zh_desc'=>'12点','en_desc'=>'12 pts'),
				13=>array('id'=>13,'type'=>2,'val'=>13,'odd'=>8,'th_desc'=>'13點','zh_desc'=>'13点','en_desc'=>'13 pts'),
				14=>array('id'=>14,'type'=>2,'val'=>14,'odd'=>12,'th_desc'=>'14點','zh_desc'=>'14点','en_desc'=>'14 pts'),
				15=>array('id'=>15,'type'=>2,'val'=>15,'odd'=>14,'th_desc'=>'15點','zh_desc'=>'15点','en_desc'=>'15 pts'),
				16=>array('id'=>16,'type'=>2,'val'=>16,'odd'=>18,'th_desc'=>'16點','zh_desc'=>'16点','en_desc'=>'16 pts'),
				17=>array('id'=>17,'type'=>2,'val'=>17,'odd'=>50,'th_desc'=>'17點','zh_desc'=>'17点','en_desc'=>'17 pts'),
				
				18=>array('id'=>18,'type'=>4,'val'=>1,'odd'=>'','th_desc'=>1,'zh_desc'=>1,'en_desc'=>1),
				19=>array('id'=>19,'type'=>4,'val'=>2,'odd'=>'','th_desc'=>2,'zh_desc'=>2,'en_desc'=>2),
				20=>array('id'=>20,'type'=>4,'val'=>3,'odd'=>'','th_desc'=>3,'zh_desc'=>3,'en_desc'=>3),
				21=>array('id'=>21,'type'=>4,'val'=>4,'odd'=>'','th_desc'=>4,'zh_desc'=>4,'en_desc'=>4),
				22=>array('id'=>22,'type'=>4,'val'=>5,'odd'=>'','th_desc'=>5,'zh_desc'=>5,'en_desc'=>5),
				23=>array('id'=>23,'type'=>4,'val'=>6,'odd'=>'','th_desc'=>6,'zh_desc'=>6,'en_desc'=>6),
				
				24=>array('id'=>24,'type'=>3,'val'=>1,'odd'=>150,'th_desc'=>'豹子1','zh_desc'=>'豹子1','en_desc'=>'豹子1'),
				25=>array('id'=>25,'type'=>3,'val'=>2,'odd'=>150,'th_desc'=>'豹子2','zh_desc'=>'豹子2','en_desc'=>'豹子2'),
				26=>array('id'=>26,'type'=>3,'val'=>3,'odd'=>150,'th_desc'=>'豹子3','zh_desc'=>'豹子3','en_desc'=>'豹子3'),
				27=>array('id'=>27,'type'=>3,'val'=>4,'odd'=>150,'th_desc'=>'豹子4','zh_desc'=>'豹子4','en_desc'=>'豹子4'),
				28=>array('id'=>28,'type'=>3,'val'=>5,'odd'=>150,'th_desc'=>'豹子5','zh_desc'=>'豹子5','en_desc'=>'豹子5'),
				29=>array('id'=>29,'type'=>3,'val'=>6,'odd'=>150,'th_desc'=>'豹子6','zh_desc'=>'豹子6','en_desc'=>'豹子6'),
		),
		
		'aFixedDice'=>array(
				1=>array('odd'=>1,'th_desc'=>'單骰#0','zh_desc'=>'单骰#0','en_desc'=>'Single#0'),
				2=>array('odd'=>2,'th_desc'=>'雙骰#0','zh_desc'=>'双骰#0','en_desc'=>'Double#0'),
				3=>array('odd'=>6,'th_desc'=>'三骰#0','zh_desc'=>'三骰#0','en_desc'=>'Three#0')
		),
		
		'rockTime'=>3,
		'betTime'=>20,
		'readyTime'=>3,
		
		
		'rwdButtonSt'=>0,		//奖励按钮是否显示
		'rwdButtonMoney'=>10,	//开奖按钮触发玩家最低下注额度
		
		
		'aShield'=>array('money'=>20000000,'ids'=>array(3,4,17,18)),
		'aBetCfg'=>array(100,1000,10000,100000),
		'aGap'=>array(100=>array(0,10000),1000=>array(10000,100000),10000=>array(100000,1000000),100000=>array(1000000)),
		'maxBet'=>1000000,
		'aRoomCfg'=>array(
				50=>array('fastMoney'=>'0-100000','minBet'=>50,'maxBet'=>100000,'roomBet'=>'50,100,200,500,1000','betShowBtn'=>100000,'serverId'=>13),
				500=>array('fastMoney'=>'100001-200000','minBet'=>100,'maxBet'=>1000000,'roomBet'=>'100,200,500,1000,2000','betShowBtn'=>1000000,'serverId'=>123),
				50000=>array('fastMoney'=>'200001-500000','minBet'=>200,'maxBet'=>5000000,'roomBet'=>'200,500,1000,2000,5000','betShowBtn'=>5000000,'serverId'=>25)
		),
		
		
		'aUpset'=>array(1,2),
		'upVal'=>10,
		
		//奖励提成
		'commission'=>array('sys'=>0.02, 'lottery'=>0.03),
		
		//彩池中奖区域列表
		'aLottery'=>array(4=>array('id'=>4, 'percent'=>30), 7=>array('id'=>7, 'percent'=>30), 24=>array('id'=>24, 'percent'=>40), 29=>array('id'=>29,'percent'=>50)),
		
		'broadSet'=>array('maxWin'=>1000000,'th_msg'=>'玩家#mnick，押中骰寶，獲得獎勵#money天降橫財！','zh_msg'=>'玩家#mnick，押中骰宝，获得奖励#money天降横财！','en_msg'=>'Player: #mnick has won #money reward from Sic bo, congratulation!'),
		'server'=>array('103.61.193.17',6221),
		'demo_server'=>array('192.168.202.101',8501),
		
		'lan'=>array('th_rwdLog'=>'押中#desc','zh_rwdLog'=>'押中#desc','en_rwdLog'=>'Win #desc','th_fixedTis'=>'單骰X1, 雙骰X2, 三骰X6','zh_fixedTis'=>'单骰X1, 双骰X2, 三骰X6','en_fixedTis'=>'Single dice bet X1, Double dice bet X2, Three dice bet X3'),
		'aPic'=>array('th_titPic'=>'1d102868.png','zh_titPic'=>'','en_titPic'=>'','th_daPic'=>'655f05b3.png','zh_daPic'=>'','en_daPic'=>'','th_xiaoPic'=>'666dd93d.png','zh_xiaoPic'=>'','en_xiaoPic'=>''),
		
		
);