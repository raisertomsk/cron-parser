<?php /** @noinspection PhpMissingFieldTypeInspection */

namespace App\Console\Commands;

use App\Account;
use App\Parser;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class Import extends Command
{

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'import';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Command description';

    private string $pidFile;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->pidFile = app()->storagePath() . '/app/working_copy.pid';
        if (file_exists($this->pidFile)) {
            $this->error('I\'m already running');
            exit(1);
        }
        if (false === file_put_contents($this->pidFile, getmypid())) {
            $this->error('Can\'t write pid file');
            exit(1);
        }
        parent::__construct();
    }

    public function __destruct()
    {
        unlink($this->pidFile);
    }

    /**
     * Execute the console command.
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $parser = new Parser(app()->storagePath() . '/app/import.csv', app()->basePath('app') . '/mapping.json');
        foreach ($parser as $line) {
            $item = new Account();
            if (!$this->validate($line)) {
                continue;
            }
            $this->fillObject($item, $line);
            $item->save();
        }
        return 0;
    }

    private function validate(array $fields): bool
    {
        // another checks have to be there
        $validation = Validator::make($fields, [
            'user_id' => 'required,exists:accounts',
            'card_number' => 'numeric,exists:accounts',
        ]);
        try {
            $validation->validate();
        } catch (Exception $e) {
            $this->error('Failed to validate: ' . json_encode($fields) . ' ' . $validation->errors()->toJson());
            return false;
        }
        return true;
    }

    private function fillObject(Account $item, array $line): void
    {
        foreach ($line as $fieldName => $value) {
            $item->{$fieldName} = $value;
        }
    }

}
