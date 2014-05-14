<ul>
  <li><b>domain:</b> {$domain.name}
    <ul>
      <li><button id="deletedomainbutton" name="{$domain.id}">delete</button></li>
    </ul>
  </li>
  <li><b>users:</b>
    <ul>
    {foreach from=$users item=user}
      <li><a href="#" class="userbutton">{$user.email}</a>
        <ul class="edituser" style="display: none;">
          <li><button class="changeuserpassword" name="{$user.id}">reset password</button><input type="text"></li>
          <li><button class="deleteuser" name="{$user.id}">delete user {$user.user}</button></li>
        </ul>
      </li>
    {/foreach}
      <li>
        <button id="newuserbutton">+</button>
        <ul id="newuser" style="display: none;">
          <li>Username: <input type="text" id="newusername"></li>
          <li>Password: <input type="text" id="newuserpassword"></li>
          <li><button id="newusersave" name="{$domain.id}">save</button> <button id="newusercancel">cancel</button></li>
        </ul>
      </li>
    </ul>
  </li>
  <li><b>aliases:</b>
    <ul>
    {foreach from=$aliases item=alias}
      <li><a href="#" class="aliasbutton">{$alias.source} - {$alias.destination}</a>
        <ul class="editalias" style="display: none;">
          <li><button class="deletealias" name="{$alias.id}">delete alias {$alias.source}</button></li>
          <li><button class="changealiastarget" name="{$alias.id}">change alias target</button><input type="text"></li>
        </ul>
      </li>
    {/foreach}
      <li>
        <button id="newaliasbutton">+</button>
        <ul id="newalias" style="display: none;">
          <li>Alias: <input type="text" id="newaliastext"></li>
          <li>Target: <input type="text" id="newaliastarget"></li>
          <li><button id="newaliassave" name="{$domain.id}">save</button> <button id="newaliascancel">cancel</button></li>
        </ul>
      </li>
    </ul>
  </li>
</ul>
