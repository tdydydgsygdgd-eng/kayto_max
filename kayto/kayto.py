import time 
import tkinter as tk

class KaytoApp:
    def __init__(self, master):
        self.master = master
        master.title("Kayto")
        master.geometry("600x400")
        master.resizable(False, False)
        master.configure(bg="#333333")
        
        self.title = tk.Label(master, text="Kayto", font=("Arial", 24), bg="#333333", fg="#ffffff")
        self.title.pack(pady=20)
        
        self.start_button = tk.Button(master, text="Start", font=("Arial", 16), bg="#4CAF50", fg="#ffffff", width=10, height=2, command=self.start)
        self.start_button.pack(pady=10)
        
    def start(self):
        self.master.destroy()
        self.timer_window = tk.Tk()
        self.timer_window.title("Timer")
        self.timer_window.geometry("600x400")
        self.timer_window.resizable(False, False)
        self.timer_window.configure(bg="#333333")
        
        self.timer_label = tk.Label(self.timer_window, text="00:00", font=("Arial", 48), bg="#333333", fg="#ffffff")
        self.timer_label.pack(pady=20)
        
        self.timer_running = True
        self.timer_seconds = 0
        self.update_timer()
        
        self.stop_button = tk.Button(self.timer_window, text="Stop", font=("Arial", 16), bg="#f44336", fg="#ffffff", width=10, height=2, command=self.stop)
        self.stop_button.pack(pady=10)
        
    def update_timer(self):
        if self.timer_running:
            self.timer_seconds += 1
            minutes = self.timer_seconds // 60
            seconds = self.timer_seconds % 60
            self.timer_label.config(text=f"{minutes:02d}:{seconds:02d}")
            self.timer_window.after(1000, self.update_timer)
    
    def stop(self):
        self.timer_running = False


root = tk.Tk()
app = KaytoApp(root)
root.mainloop() 