<ul>
  <li><b>domains:</b></li>
{foreach from=$domains item=domain}
  <li><a href="#" class="domainbutton" data-name="{$domain.name}">{$domain.label}</a></li>
{/foreach}
  <li class="is-hidden" id="newdomainli"><input type="text" value="newdomain.com" id="newdomaintext"><br><button id="newdomainsave">save</button> &nbsp; &nbsp; <button id="newdomaincancel">cancel</button></li>
  <li id="newdomainbuttonli"><button id="newdomainbutton">+</button></li>
</ul>
