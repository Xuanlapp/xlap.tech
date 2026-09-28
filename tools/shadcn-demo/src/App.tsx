import { useState } from "react"

import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"

export function App() {
  const [enabled, setEnabled] = useState(true)
  const [name, setName] = useState("")

  return (
    <main className="min-h-svh bg-muted/30 px-6 py-12">
      <div className="mx-auto flex max-w-3xl flex-col gap-8">
        <header className="flex flex-col gap-3">
          <div className="flex items-center gap-2"><Badge variant="secondary">XLAP thử nghiệm</Badge><span className="text-sm text-muted-foreground">shadcn/ui + Vite</span></div>
          <div><h1 className="text-3xl font-semibold tracking-tight">Màn hình thử shadcn/ui</h1><p className="mt-2 text-muted-foreground">Đây là khu vực an toàn để thử component trước khi đưa vào XLAP chính.</p></div>
        </header>
        <section className="grid gap-6 md:grid-cols-[1.15fr_0.85fr]">
          <Card><CardHeader><CardTitle>Cấu hình workflow</CardTitle><CardDescription>Ví dụ form dùng các component có thể tái sử dụng.</CardDescription></CardHeader><CardContent className="space-y-6">
            <div className="space-y-2"><Label htmlFor="workflow-name">Tên workflow</Label><Input id="workflow-name" placeholder="Ví dụ: Etsy listing" value={name} onChange={(event) => setName(event.target.value)} /></div>
            <div className="flex items-center justify-between rounded-lg border p-4"><div className="space-y-1"><Label htmlFor="workflow-enabled">Cho phép chạy tự động</Label><p className="text-sm text-muted-foreground">Bật/tắt bằng component Switch.</p></div><Switch id="workflow-enabled" checked={enabled} onCheckedChange={setEnabled} /></div>
            <Button className="w-full" disabled={!name.trim()}>Lưu cấu hình</Button>
          </CardContent></Card>
          <Card><CardHeader><CardTitle>Trạng thái</CardTitle><CardDescription>Giá trị hiển thị thay đổi theo thao tác.</CardDescription></CardHeader><CardContent className="space-y-4">
            <div className="flex items-center justify-between rounded-lg bg-muted/60 p-4"><span className="text-sm">Workflow</span><Badge variant={enabled ? "default" : "outline"}>{enabled ? "Đang bật" : "Đã tắt"}</Badge></div>
            <div className="flex items-center justify-between rounded-lg bg-muted/60 p-4"><span className="text-sm">Tên hiện tại</span><span className="max-w-32 truncate text-sm font-medium">{name || "Chưa đặt"}</span></div>
            <Dialog><DialogTrigger asChild><Button variant="outline" className="w-full">Xem dialog mẫu</Button></DialogTrigger><DialogContent><DialogHeader><DialogTitle>Component hoạt động rồi</DialogTitle><DialogDescription>Dialog này được cài bằng shadcn CLI và nằm trong src/components/ui.</DialogDescription></DialogHeader></DialogContent></Dialog>
          </CardContent></Card>
        </section>
      </div>
    </main>
  )
}

export default App
