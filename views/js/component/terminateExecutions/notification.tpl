<div class="component termination-area">
    <section class="fb-container">
        <div class="feedback-warning status-active">
            <span class="icon-warning"></span>
            <div class="notification-message">
                <p>
                    {{notificationMessage}}
                </p>
            </div>

            <div class="in-progress-list">
                <p class="b">{{__ "Assessments in progress:"}}</p>
                <ul>
                    {{#each aggregatedData}}
                        <li>- {{this.label}} / {{this.total}}</li>
                    {{/each}}
                </ul>
            </div>
        </div>


        <div class="button-area">
            <span class="btn-info cancel-button">
                <span class="icon-close"></span>
                {{__ "Cancel"}}
            </span>

            <span class="btn-warning force-terminate-button">
                <span class="icon-warning"></span>
                {{__ "Force end and synchronize"}}
            </span>
        </div>
    <div>
</div>