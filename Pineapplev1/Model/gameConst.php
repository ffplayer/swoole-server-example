<?php
class gameConst{
	//以下是系统返回数据命令
	const S_LOGIN   			= 0x201;    //登录成功
	const S_LOGIN_FAIL   		= 0x202;    //登录失败
	const S_BROADCAST_LOGIN		= 0x203;    //广播用户登录
	const S_LOGOUT  			= 0x204;    //登出成功
	const S_FIVECARD   			= 0x205;    //发5张牌
	const S_BROADCAST_READY     = 0x206;    //周知准备
	const S_BROADCAST_PUTCARD	= 0x207;    //摆牌信息
	const S_BROADCAST_SIT       = 0x208;    //广播有人坐下
	const S_BROADCAST_STANDUP   = 0x209;    //广播有人站起
	const S_BROADCAST_QUEUE   	= 0x20a;    //广播排队人数
	const S_MONEY_LIMIT   		= 0x20b;    //游戏币不够
	const S_THREECARD			= 0x20c;   //发3张牌
	const S_RELOGIN				= 0x20d;   //重复登录
	const S_BROADCAST_FEE	    = 0x20e;   //广播台费
	const S_BROADCAST_STOP	    = 0x20f;   //停服
	const S_HEARTBEAT 		    = 0x210;   //心跳返回
	const S_BROADCAST_OPT 		= 0x211;   //周知下一个操作者
	const S_BROADCAST_ADDFRIEND = 0x212;   //周知添加好友
	const S_BROADCAST_CHAT 		= 0x003;   //聊天
	const S_BROADCAST_PHIZ 		= 0x004;   //表情发送
	const S_BROADCAST_OVER 		= 0x213;   //结算
	const S_BROADCAST_PUTOK 	= 0x214;   //提交成功
	const S_BROADCAST_ADDFRIENDSUC = 0x215;   //周知添加好友成功
}

