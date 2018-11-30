<div class="modal sync-report-modal">
    <section>
        <h1>
            {{__ 'Synchronization report data'}}
        </h1>
        {{#each data}}
        <h2>{{@key}}:</h2>
        <div><pre>{{this}}</pre></div>
        <hr>
        {{/each}}
    </section>
</div>