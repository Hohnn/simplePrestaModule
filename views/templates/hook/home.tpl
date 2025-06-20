<div class="mymodule-testamonial-form" style="margin-top:30px;">
  <h3>Laissez un témoignage</h3>

  {if $success}
    <div class="alert alert-success">Merci pour votre témoignage !</div>
  {/if}

  {if $errors}
    <div class="alert alert-danger">
      <ul>
        {foreach from=$errors item=error}
          <li>{$error}</li>
        {/foreach}
      </ul>
    </div>
  {/if}

  <form method="post" action="{$form_action|escape:'html'}">
    <div class="form-group">
      <label for="content">Votre témoignage :</label>
      <textarea name="content" class="form-control" required></textarea>
    </div>
    <button type="submit" name="submit_testamonial_home" class="btn btn-primary mt-2">Envoyer</button>
  </form>
</div>

<div class="mymodule-testamonial-list" style="margin-top:30px;">
    <h3>Témoignages récents</h3>
    {if $testamonials}
        <ul class="list-group">
            {foreach from=$testamonials item=testamonial}
                <li class="list-group-item">
                    <p>{$testamonial->message}</p>
                    <div class="text-muted">
                    {$testamonial->getFullName()}
                    {$testamonial->getDateFormatted()}
                    </div>
                </li>
            {/foreach}
        </ul>
    {else}
        <p>Aucun témoignage trouvé.</p>
    {/if}
</div>
