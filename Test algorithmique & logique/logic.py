import heapq
import math
from typing import Dict, List, Tuple, Optional
from dataclasses import dataclass

# =============================================================================
# OPTIMAL PATH FINDING - A* ALGORITHM
# =============================================================================

@dataclass
class Point:
    x: float
    y: float
    
    def distance_to(self, other: 'Point') -> float:
        """Calculate Euclidean distance between two points"""
        return math.sqrt((self.x - other.x)**2 + (self.y - other.y)**2)

@dataclass
class Node:
    point: Point
    g_cost: float = 0  # Cost from start
    h_cost: float = 0  # Heuristic cost to goal
    f_cost: float = 0  # Total cost
    parent: Optional['Node'] = None
    
    def __lt__(self, other):
        return self.f_cost < other.f_cost

class PathFinder:
    def __init__(self):
        self.graph = {}  # adjacency list: {Point: [(Point, distance), ...]}
    
    def add_edge(self, point1: Point, point2: Point, distance: float = None):
        """Add bidirectional edge between two points"""
        if distance is None:
            distance = point1.distance_to(point2)
        
        if point1 not in self.graph:
            self.graph[point1] = []
        if point2 not in self.graph:
            self.graph[point2] = []
            
        self.graph[point1].append((point2, distance))
        self.graph[point2].append((point1, distance))
    
    def a_star(self, start: Point, goal: Point) -> Tuple[List[Point], float]:
        """
        A* algorithm to find optimal path between two points
        Returns: (path, total_distance)
        """
        if start not in self.graph or goal not in self.graph:
            return [], float('inf')
        
        open_set = []
        closed_set = set()
        
        start_node = Node(start, 0, start.distance_to(goal))
        start_node.f_cost = start_node.h_cost
        heapq.heappush(open_set, start_node)
        
        node_map = {start: start_node}
        
        while open_set:
            current = heapq.heappop(open_set)
            
            if current.point == goal:
                # Reconstruct path
                path = []
                node = current
                while node:
                    path.append(node.point)
                    node = node.parent
                path.reverse()
                return path, current.g_cost
            
            closed_set.add(current.point)
            
            # Check all neighbors
            for neighbor_point, edge_cost in self.graph[current.point]:
                if neighbor_point in closed_set:
                    continue
                
                tentative_g = current.g_cost + edge_cost
                
                if neighbor_point not in node_map:
                    neighbor_node = Node(neighbor_point)
                    node_map[neighbor_point] = neighbor_node
                else:
                    neighbor_node = node_map[neighbor_point]
                    if tentative_g >= neighbor_node.g_cost:
                        continue
                
                # This path is better
                neighbor_node.parent = current
                neighbor_node.g_cost = tentative_g
                neighbor_node.h_cost = neighbor_point.distance_to(goal)
                neighbor_node.f_cost = neighbor_node.g_cost + neighbor_node.h_cost
                
                heapq.heappush(open_set, neighbor_node)
        
        return [], float('inf')  # No path found

# =============================================================================
# DEMONSTRATION AND TESTING
# =============================================================================

def demonstrate_pathfinding():
    print("=== OPTIMAL PATH FINDING DEMONSTRATION ===\n")
    
    pathfinder = PathFinder()
    
    # Create a city grid with named points
    points = {
        'A': Point(0, 0),    # Starting point
        'B': Point(1, 0),
        'C': Point(2, 0),
        'D': Point(0, 1),
        'E': Point(1, 1),    # Center intersection
        'F': Point(2, 1),
        'G': Point(0, 2),
        'H': Point(1, 2),
        'I': Point(2, 2)     # Destination
    }
    
    # Add roads (bidirectional edges) with automatic distance calculation
    roads = [
        ('A', 'B'), ('B', 'C'),  # Bottom row
        ('A', 'D'), ('B', 'E'), ('C', 'F'),  # Vertical connections
        ('D', 'E'), ('E', 'F'),  # Middle row
        ('D', 'G'), ('E', 'H'), ('F', 'I'),  # More verticals
        ('G', 'H'), ('H', 'I')   # Top row
    ]
    
    for p1_name, p2_name in roads:
        pathfinder.add_edge(points[p1_name], points[p2_name])
        print(f"Added road: {p1_name} ↔ {p2_name}")
    
    print(f"\nCity Grid Layout:")
    print("G(0,2) — H(1,2) — I(2,2)")
    print("  |        |        |   ")
    print("D(0,1) — E(1,1) — F(2,1)")
    print("  |        |        |   ")
    print("A(0,0) — B(1,0) — C(2,0)")
    
    # Find optimal path from A to I
    print(f"\nFinding optimal path from A(0,0) to I(2,2)...")
    path, distance = pathfinder.a_star(points['A'], points['I'])
    
    if path:
        print(f"\nOptimal Route Found:")
        for i, point in enumerate(path):
            name = next(k for k, v in points.items() if v == point)
            print(f"  {i+1}. {name} at ({point.x}, {point.y})")
        
        print(f"\nTotal Distance: {distance:.2f} units")
        print(f"Number of steps: {len(path) - 1}")
    else:
        print("No path found!")
    
    # Test multiple routes
    test_routes = [
        ('A', 'F'),  # Bottom-left to middle-right
        ('G', 'C'),  # Top-left to bottom-right
        ('B', 'H'),  # Direct vertical
    ]
    
    print(f"\n=== Testing Additional Routes ===")
    for start_name, end_name in test_routes:
        path, distance = pathfinder.a_star(points[start_name], points[end_name])
        route_names = [next(k for k, v in points.items() if v == point) for point in path]
        print(f"{start_name} → {end_name}: {' → '.join(route_names)} (Distance: {distance:.2f})")

