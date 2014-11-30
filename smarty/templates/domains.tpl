<ul>
  <li><b>domains:</b></li>
{foreach from=$domains item=domain}
  <li><a href="#" class="domainbutton">{$domain.name}</a></li>
{/foreach}
  <li style="display: none;" id="newdomainli"><input type="text" value="newdomain.com" id="newdomaintext"/><br/><button id="newdomainsave">save</button> &nbsp; &nbsp; <button id="newdomaincancel">cancel</button></li>
  <li id="newdomainbuttonli"><button id="newdomainbutton">+</button></li>
</ul>
