<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingScript;

class ProspectingScriptLibraryService
{
    /**
     * @return array<int, array{id: string, name: string, content: string, sort_order: int}>
     */
    public function scriptsForProspectingTool(): array
    {
        $scripts = ProspectingScript::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($scripts->isEmpty()) {
            return $this->defaultScripts();
        }

        return $scripts->map(function (ProspectingScript $script): array {
            return [
                'id' => 'script-'.$script->id,
                'name' => $script->name,
                'content' => $script->content,
                'sort_order' => $script->sort_order,
            ];
        })->all();
    }

    /**
     * @return array<int, array{id: string, name: string, content: string, sort_order: int}>
     */
    private function defaultScripts(): array
    {
        return [
            [
                'id' => 'expired-script',
                'name' => 'Expired Script',
                'content' => <<<'TEXT'
I noticed your home was listed on the market and recently expired.

Out of curiosity, Why do you think it didn't sell?

I specialize in getting expired listings to sell by evaluating what went wrong and fixing that.

Are you still interested in selling ?

If I can show you a different strategy on how to get your home sold will you take some time to sit down with me and go over the details?

I'm available  Friday at 3pm or Saturday at Noon.

Which time works best for you ?
TEXT,
                'sort_order' => 1,
            ],
            [
                'id' => 'fsbo-script',
                'name' => 'FSBO',
                'content' => <<<'TEXT'
[Client Name]

My name is [Your Name] I'm a local realtor calling about the home for sale.

Is it still available?

The reason for my call is your property came across my desk this morning, and I was unsure if it's currently listed as for-sale by owner or if you have it listed with the real estate agent.

How long have you been on the market?

Do you have any offers in hand yet?

Interesting! A lot of homes are selling right now. I'm surprised yours hasn't sold yet.

What's the current asking price?

We use a third-party system. We're calling to make sure that the information is correct.

How many bedrooms and bathrooms do you have?

Approximately how many square feet do you have?

Do you have a basement?

Is it finished, unfinished, or partly finished?

How did you determine your asking price?

Would you say that you're currently priced at market value or a little bit low or high to leave room for negotiations?

Are you willing to adjust your price down when working with the buyer?

Are you offering out a 3% commission to an agent like myself if I bring you a buyer?

What was the main reason why you decided to sell it yourself rather than list it with an agent and get more exposure?

What I hear is you want to walk away with as much money as possible, correct?

Mr. Seller, I'm sure you've come up with a bottom-line number that you can't go below, to where it wouldn't make sense to sell, right?

As long as you get that number, Mr. Seller, does it really matter how you sell or how much commission you pay, as long as you get what you want?

If I could show you how to put the same amount of money in your pocket or more and help you sell it sooner rather than later. What would stop you from at least wanting to take a look at that if there's no cost or obligation to you?

Lets setup a time to meet.

I'm available Friday at 3PM or Saturday at noon, which time works best for you ?
TEXT,
                'sort_order' => 2,
            ],
        ];
    }
}
