local curl = require "lcurl.safe"
local json = require "cjson.safe"

local url1 = pd.getConfig("Baidu","accelerateURL")
if url1 == "" then
	url1 = pd.input("请输入服务激活码：")
	pd.setConfig("Baidu","accelerateURL",url1)
	--pd.logInfo(url1)
end

script_info = {
	["title"] = "MO-Down",
	["color"] = "#5fa1fa",
	["version"] = "7.2.0",
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
		task:setError(-1,"获取链接失败，请重新下载或重启软件尝试自动恢复")
		pd.logError('获取链接超时，请重新下载或重启软件尝试自动恢复')
		return true
		end
		dlink = resultjson.list[1].dlink
    end



	if 200==200 then
		local url="http://124.223.112.186:99/Parsing/SVIPParsing.php?PCSPath="..pd.base64Encode(string.gsub(string.gsub(dlink, "https://d.pcs.baidu.com/file/", "&path="), "?fid", "&fid")).."&Activation="..pd.getConfig("Baidu","accelerateURL")
		local data = ""
		--pd.logInfo(url)
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
			end
		    local num = 1
			downloadURL = jss.urls[num].url
			task:setUris(downloadURL)
			task:setOptions("user-agent", j.ua)
			task:setOptions("header", "Range:bytes=0-0")
			task:setIcon("icon/accelerate.png", "MO引擎加速中")
			task:setOptions("split", "8")
			task:setOptions("piece-length", "1M")
			task:setOptions("allow-piece-length-change", "true")
			task:setOptions("enable-http-pipelining", "true")
			return true
		else
			if j.code==405 then
				task:setError(j.code,j.messgae)
				return true
			end
			if j.code == 404 then
			    urll = pd.input("激活服务码错误，请输入正确的激活服务码：")
			    pd.setConfig("Baidu","accelerateURL",urll)
			    return true
			end
		end
	end
end