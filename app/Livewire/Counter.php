<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Counter Component
 * 
 * Một component Livewire đơn giản được sử dụng để quản lý state của một bộ đếm.
 * Component này cho phép người dùng tăng giá trị counter thông qua một button.
 * 
 * @package App\Livewire
 * @author XLAP
 * @version 1.0
 */
class Counter extends Component
{
    /**
     * Giá trị hiện tại của bộ đếm
     * 
     * Thuộc tính public này được Livewire tự động track để cập nhật view
     * mỗi khi giá trị thay đổi
     * 
     * @var int
     */
    public $count = 0;

    /**
     * Tăng giá trị counter lên 1
     * 
     * Method này được gọi từ view khi người dùng click vào button.
     * Livewire sẽ tự động cập nhật view sau khi method hoàn tất.
     * 
     * Flow: User click button → increment() được gọi → $count tăng 1 → 
     *       Livewire re-render view → UI cập nhật giá trị mới
     * 
     * @return void
     */
    public function increment(): void
    {
        $this->count++;
    }

    /**
     * Render component view
     * 
     * Method này được Livewire tự động gọi để render component.
     * Nó trả về Blade view file tương ứng với component.
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.counter');
    }
}