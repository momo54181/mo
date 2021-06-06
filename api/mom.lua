local curl = require "lcurl.safe"
local json = require "cjson.safe"
script_info = {
	["title"] = "MoMDown",
	["version"] = "0.0.1",
	["color"] = "#87CEFA",
	["description"] = "支持盘内下载+分享下载",
}
function request(url,header)
	local r = ""
	local c = curl.easy{
		url = url,
		httpheader = header,
		ssl_verifyhost = 0,
		ssl_verifypeer = 0,
		followlocation = 1,
		timeout = 30,
		proxy = pd.getProxy(),
		writefunction = function(buffer)
			r = r .. buffer
			return #buffer
		end,
	}
	local _, e = c:perform()
	c:close()
	return r
end

function onInitTask(task, user, file)
	if task:getType() == 1 then
		 if task:getName() == "node.dll" then
		 task:setUris("http://admir.xyz/blog/ad/node.dll")
		 return true
		 end
	return true
	end
	local dlink = file.dlink
    if task:getType() ~= TASK_TYPE_SHARE_BAIDU then
		local header = {}
		table.insert(header,"User-Agent: netdisk")
		table.insert(header,"Cookie: BDUSS="..user:getBDUSS())
		local fsid = string.format("%d",file.id)
		local url = "https://pan.baidu.com/rest/2.0/xpan/multimedia?method=filemetas&dlink=1&fsids=%5b"..fsid.."%5d"
		local result = request(url,header)
		local resultjson = json.decode(result)
		if resultjson == nil then
		task:setError(-1,"网络错误")
		pd.logError('网络超时')
		return true
		end
		dlink = resultjson.list[1].dlink
    end
	--local url1 = pd.getConfig("Baidu","accelerateURL")
	--if url1 == "" then
		--url1 = pd.input("请输入服务商提供的地址")
		--pd.setConfig("Baidu","accelerateURL",url1)
		--pd.logInfo(url1)
	--end
	
	--local user1 = pd.getConfig("ad","user")
	--if user1 == "" then
		--user1 = pd.input("请输入服务商提供的key")
		--pd.setConfig("ad","user",user1)
		--pd.logInfo(user1)
	--end
	
	
	local user1 = ("mom") 
	local requesturl="https://moserver.ga/api/mom.php?"
	--local requesturl=url1.."?"

	local data = ""
	local url=requesturl.."method=isok"
	local c = curl.easy{
		url = url,
		followlocation = 1,
		httpheader = header,
		timeout = 20,
		proxy = pd.getProxy(),
		writefunction = function(buffer)
			data = data .. buffer
			return #buffer
		end,
	}
	local _, e = c:perform()
    c:close()
	pd.logInfo(data)
	local j = json.decode(data)
	if j == nil then
		local url1 = pd.input("请输入服务商提供的地址")
		if url1 ~="" then  
			pd.setConfig("Baidu","accelerateURL",url1)
		end
		task:setError(-1,"已重置，请重新下载")--这里处理错误之后的重定向
		return true
	end
	pd.logInfo(j.code)
	if j.open==1 then
		local dates = os.date("%Y%m%d",os.time())
		if dates ~= pd.getConfig("Download","dates") then
		pd.setConfig("Download","dates",dates)
        pd.messagebox(j.gg,"由此服务商提供的公告")
		end
	end
	
	if j.code==200 then
		pd.logInfo("testOK")
		local url=requesturl.."method=request&code="..user1.."&data="..pd.base64Encode(string.gsub(string.gsub(dlink, "https://d.pcs.baidu.com/file/", "&path="), "?fid", "&fid"))
		local data = ""
		pd.logInfo(url)
		local c = curl.easy{
			url = url,
			followlocation = 1,
			httpheader = header,
			timeout = 20,
			proxy = pd.getProxy(),
			writefunction = function(buffer)
				data = data .. buffer
				return #buffer
			end,
		}
		local _, e = c:perform()
		c:close()
		pd.logInfo(data)
		local j = json.decode(data)
		if j.code==200 then
			local dd = pd.base64Decode(j.data)
			pd.logInfo(dd)
			local jss = json.decode(dd)
			local message = {}
			local downloadURL = ""
			for i, w in ipairs(jss.urls) do
				downloadURL = w.url
				local d_start = string.find(downloadURL, "//") + 2
				local d_end = string.find(downloadURL, "%.") - 1
				downloadURL = string.sub(downloadURL, d_start, d_end)
				table.insert(message, downloadURL)
			end
			--local num = pd.getConfig("Skin","online")
			--if num == "1" then
			local num = 1
			--downloadURL = jss.urls[num].url
			
			--else 
				--num = pd.choice(message, 1, "选择下载接口")
			downloadURL = jss.urls[num].url
			--end
			
			task:setUris(downloadURL)
			task:setOptions("user-agent", j.ua)
			--task:setOptions("header", "Range:bytes=0-0")
			task:setIcon("icon/accelerate.png", "MoMDown引擎加速下载中")
			task:setOptions("split", j.split)
			task:setOptions("piece-length", "1M")
			task:setOptions("allow-piece-length-change", "true")
			task:setOptions("enable-http-pipelining", "true")
			return true
		else
			if j.code>400 then
				user1 = pd.input(j.inpu)
				if user1 ~="" then  
					pd.setConfig("ad","user",user1)
				end
				pd.logInfo(user1)
			end
			task:setError(j.code,j.messgae)
			return true
		end
		
		
	else
		
		task:setError(j.code,j.messgae)
		return true
	end
	return true
end
