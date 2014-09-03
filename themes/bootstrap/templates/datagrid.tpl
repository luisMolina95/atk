{if !empty($top)}
    <div class="row">
        <div class="col-md-12">
            {$top}
        </div>
    </div>
{/if}
{if !empty($index) || !empty($editcontrol)}
    <div class="row">
        <div class="col-md-12">
            {if !empty($editcontrol)}{$editcontrol}{/if} {if !empty($index)}{$index}{/if}
        </div>
    </div>
{elseif !empty($paginator) || !empty($limit)}
    <div class="row">
        <div class="col-md-8">
            {if !empty($editcontrol)}{$editcontrol}{/if} {if !empty($paginator)}{$paginator}{/if}
        </div>
        <div class="col-md-4 text-right">
            {if !empty($limit)}{$limit}{/if}
        </div>
    </div>
{/if}
{if !empty($list)}
    <div class="row">
        <div class="col-md-12">
            {$list}
        </div>
    </div>
{/if}
{if !empty($norecordsfound)}
    <div class="row">
        <div class="col-md-12">
            <i>{$norecordsfound}</i>
        </div>
    </div>
{/if}
{if !empty($paginator) || !empty($summary)}
    <div class="row">
        <div class="col-md-8">
            {if !empty($paginator)}{$paginator}{/if}
        </div>
        <div class="col-md-4 text-right">
            {if !empty($summary)}{$summary}{/if}
        </div>
    </div>
{/if}
{if !empty($bottom)}
    <div class="row">
        <div class="col-md-12">
            {$bottom}
        </div>
    </div>
{/if}