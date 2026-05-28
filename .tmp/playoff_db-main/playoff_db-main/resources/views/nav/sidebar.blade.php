<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">

    <div id="drawer-navigation"
         class="fixed top-0 left-0 z-40 w-64 h-screen p-4 overflow-y-auto transition-transform -translate-x-full bg-white dark:bg-gray-800"
         tabindex="-1" aria-labelledby="drawer-navigation-label">
        <h5 id="drawer-navigation-label" class="text-base font-semibold text-gray-500 uppercase dark:text-gray-400">
            Menu</h5>
        <button type="button" data-drawer-hide="drawer-navigation" aria-controls="drawer-navigation"
                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 absolute top-2.5 end-2.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
            <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                 xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                      clip-rule="evenodd"></path>
            </svg>
            <span class="sr-only">Close menu</span>
        </button>
        <div class="py-4 overflow-y-auto">
            <ul class="space-y-2 font-medium">
                <!-- Program item with expandable submenu -->
                <li>
                    <a href="#"
                       class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                       id="program-button">
                        <x-svg.program
                                class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white">
                        </x-svg.program>
                        <span class="ms-3">Program</span>
                    </a>
                    <!-- Submenu for Program -->
                    <ul id="program-submenu" class="space-y-2 pl-6 mt-2 ">
                        <li>
                            <x-nav-link href="{{ route('program', 'program-list') }}"
                                        :active="request()->routeIs('program')" class="text-white">
                                <img class="h-4 mr-1" src="https://www.mlbstatic.com/team-logos/league-on-dark/1.svg"
                                     alt=""> {{ __('Program') }}
                            </x-nav-link>
                        </li>
                        <li>
                            <a href="#"
                               class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                <span class="ms-3">Icon Layout</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li>
                    <a href="#"
                       class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group"
                       id="player-stats-button">
                        <x-svg.player
                                class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-white dark:group-hover:text-white">
                        </x-svg.player>

                        <span class="ms-3">Player Stats</span>
                    </a>
                    <!-- Submenu for Player Stats -->
                    <ul id="player-stats-submenu" class="space-y-2 pl-6 mt-2  ">
                        <li>
                            <x-nav-link href="{{ route('mlb', 'new-player') }}" :active="request()->routeIs('mlb')"
                                        class="text-white">
                                <img class="h-4 mr-1" src="https://www.mlbstatic.com/team-logos/league-on-dark/1.svg"
                                     alt=""> {{ __('MLB') }}
                            </x-nav-link>
                        </li>
                        <li>
                            <x-nav-link href="{{ route('nba', 'nba-new-player') }}" :active="request()->routeIs('nba')"
                                        class="text-white">
                                <x-icons.nba_logo class="h-4 mr-1"/>
                                {{ __('NBA') }}
                            </x-nav-link>
                        </li>
                        <li>
                            <x-nav-link href="{{ route('wnba', 'new-player') }}"
                                        :active="request()->routeIs('wnba')"
                                        class="text-white">
                                <img class="h-4 mr-1"
                                     src="https://cdn.wnba.com/static/next/images/logos/wnba-secondary-logo.svg"
                                     alt=""> {{ __('WNBA') }}
                            </x-nav-link>
                        </li>


                    </ul>
                </li>
                <li>
                    <a href="#"
                       class="flex items-center p-2 text-white rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                        <span class="ms-3">Sport Logos</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <script> const programButton = document.getElementById('program-button');
        const programSubmenu = document.getElementById('program-submenu');
        const drawerButton = document.querySelector('[data-drawer-target="drawer-navigation"]');
        const drawer = document.getElementById('drawer-navigation');
        const closeButton = drawer.querySelector('[data-drawer-hide="drawer-navigation"]');

        drawerButton.addEventListener('click', () => {
            drawer.classList.remove('-translate-x-full');
            drawer.classList.add('translate-x-0');
        });

        closeButton.addEventListener('click', () => {
            drawer.classList.add('-translate-x-full');
            drawer.classList.remove('translate-x-0');
        });
        programButton.addEventListener('click', () => {
            // Toggle the visibility of the submenu
            programSubmenu.classList.toggle('hidden');
        });
        const playerStatsButton = document.getElementById('player-stats-button');
        const playerStatsSubmenu = document.getElementById('player-stats-submenu');

        playerStatsButton.addEventListener('click', () => {
            playerStatsSubmenu.classList.toggle('hidden');
        });
    </script>
</nav>