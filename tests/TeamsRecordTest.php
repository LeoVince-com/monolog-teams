<?php

use LeoVince\MonologTeams\TeamsRecord;

test('Can instantiate TeamsRecord class', function () {
    $teamsRecord = new TeamsRecord();

    expect($teamsRecord)->toBeInstanceOf(TeamsRecord::class);
});
